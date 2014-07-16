<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Repository;

use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Loader\LoaderInterface;
use Composer\Package\Version\VersionParser;
use Composer\Repository\InvalidRepositoryException;
use Composer\Repository\Vcs\VcsDriverInterface;
use Composer\Repository\VcsRepository;
use Fxp\Composer\AssetPlugin\Assets;
use Fxp\Composer\AssetPlugin\Package\LazyCompletePackage;
use Fxp\Composer\AssetPlugin\Package\Loader\LazyAssetPackageLoader;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Asset VCS repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class AssetVcsRepository extends VcsRepository
{
    /**
     * @var AssetTypeInterface
     */
    protected $assetType;

    /**
     * @var VersionParser
     */
    protected $versionParser;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var LoaderInterface
     */
    protected $loader;

    /**
     * Constructor.
     *
     * @param array           $repoConfig
     * @param IOInterface     $io
     * @param Config          $config
     * @param EventDispatcher $dispatcher
     * @param array           $drivers
     */
    public function __construct(array $repoConfig, IOInterface $io, Config $config, EventDispatcher $dispatcher = null, array $drivers = null)
    {
        $drivers = $drivers ?: array(
            'github' => 'Fxp\Composer\AssetPlugin\Repository\Vcs\GitHubDriver',
            'git'    => 'Fxp\Composer\AssetPlugin\Repository\Vcs\GitDriver',
        );
        $assetType = substr($repoConfig['type'], 0, strpos($repoConfig['type'], '-'));
        $assetType = Assets::createType($assetType);
        $repoConfig['asset-type'] = $assetType->getName();
        $repoConfig['filename'] = $assetType->getFilename();
        $this->assetType = $assetType;
        $this->dispatcher = $dispatcher;

        parent::__construct($repoConfig, $io, $config, $dispatcher, $drivers);
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        $this->packages = array();

        /* @var VcsDriverInterface $driver */
        $driver = $this->getDriver();
        if (!$driver) {
            throw new \InvalidArgumentException('No driver found to handle Asset VCS repository '.$this->url);
        }

        $this->versionParser = new VersionParser();
        if (!$this->loader) {
            $this->loader = new ArrayLoader($this->versionParser);
        }

        try {
            if ($driver->hasComposerFile($driver->getRootIdentifier())) {
                $data = $driver->getComposerInformation($driver->getRootIdentifier());
                $this->packageName = !empty($data['name']) ? $data['name'] : null;
            }
        } catch (\Exception $e) {
            if ($this->verbose) {
                $this->io->write('<error>Skipped parsing '.$driver->getRootIdentifier().', '.$e->getMessage().'</error>');
            }
        }

        $this->initTags($driver);
        $this->initBranches($driver);
        $driver->cleanup();

        if (!$this->getPackages()) {
            throw new InvalidRepositoryException('No valid ' . $this->assetType->getFilename() . ' was found in any branch or tag of '.$this->url.', could not load a package from it.');
        }
    }

    /**
     * Initializes all tags.
     *
     * @param VcsDriverInterface $driver
     */
    protected function initTags(VcsDriverInterface $driver)
    {
        $verbose = $this->verbose;
        $prefixPackage = $this->assetType->getComposerVendorName() . '/';
        $packageClass = 'Fxp\Composer\AssetPlugin\Package\LazyCompletePackage';

        foreach ($driver->getTags() as $tag => $identifier) {
            $packageName = $prefixPackage . ($this->packageName ?: $this->url);

            // strip the release- prefix from tags if present
            $tag = str_replace('release-', '', $tag);

            if (!$parsedTag = $this->validateTagAsset($tag)) {
                if ($verbose) {
                    $this->io->write('<warning>Skipped tag '.$tag.', invalid tag name</warning>');
                }
                continue;
            }

            $data = $this->createMockOfPackageConfig($packageName, $tag);

            // manually versioned package
            if (isset($data['version'])) {
                $data['version'] = $this->assetType->getVersionConverter()->convertVersion($data['version']);
                $data['version_normalized'] = $this->versionParser->normalize($data['version']);
            } else {
                // auto-versioned package, read value from tag
                $data['version'] = $this->assetType->getVersionConverter()->convertVersion($tag);
                $data['version_normalized'] = $parsedTag;
            }

            // make sure tag packages have no -dev flag
            $data['version'] = preg_replace('{[.-]?dev$}i', '', (string) $data['version']);
            $data['version_normalized'] = preg_replace('{(^dev-|[.-]?dev$)}i', '', (string) $data['version_normalized']);

            // broken package, version doesn't match tag
            if ($data['version_normalized'] !== $parsedTag) {
                $data['version_normalized'] = $parsedTag;
            }

            $packageData = $this->preProcessAsset($data);
            $package = $this->loader->load($packageData, $packageClass);
            $packageAlias = $this->loader->load($packageData, $packageClass);
            $lazyLoader = $this->createLazyLoader('tag', $identifier, $packageData, $driver);
            /* @var LazyCompletePackage $package */
            /* @var LazyCompletePackage $packageAlias */
            $package->setLoader($lazyLoader);
            $packageAlias->setLoader($lazyLoader);
            $this->addPackage($package);
            $this->addPackage($packageAlias);
        }

        if (!$this->verbose) {
            $this->io->overwrite('', false);
        }
    }

    /**
     * Initializes all branches.
     *
     * @param VcsDriverInterface $driver
     */
    protected function initBranches(VcsDriverInterface $driver)
    {
        $verbose = $this->verbose;
        $prefixPackage = $this->assetType->getComposerVendorName() . '/';
        $packageClass = 'Fxp\Composer\AssetPlugin\Package\LazyCompletePackage';

        foreach ($driver->getBranches() as $branch => $identifier) {
            $packageName = $prefixPackage . ($this->packageName ?: $this->url);

            if (!$parsedBranch = $this->validateBranchAsset($branch)) {
                if ($verbose) {
                    $this->io->write('<warning>Skipped branch '.$branch.', invalid name</warning>');
                }
                continue;
            }

            $data = $this->createMockOfPackageConfig($packageName, $branch);
            $data['version_normalized'] = $parsedBranch;

            // make sure branch packages have a dev flag
            if ('dev-' === substr((string) $parsedBranch, 0, 4) || '9999999-dev' === $parsedBranch) {
                $data['version'] = 'dev-' . $data['version'];
            } else {
                $data['version'] = preg_replace('{(\.9{7})+}', '.x', (string) $parsedBranch);
            }

            $packageData = $this->preProcessAsset($data);
            /* @var LazyCompletePackage $package */
            $package = $this->loader->load($packageData, $packageClass);
            $lazyLoader = $this->createLazyLoader('branch', $identifier, $packageData, $driver);
            $package->setLoader($lazyLoader);
            $this->addPackage($package);
        }

        if (!$this->verbose) {
            $this->io->overwrite('', false);
        }
    }

    /**
     * Creates the mock of package config.
     *
     * @param string $name    The package name
     * @param string $version The version
     *
     * @return array The package config
     */
    protected function createMockOfPackageConfig($name, $version)
    {
        return array(
            'name'    => $name,
            'version' => $version,
            'type'    => $this->assetType->getComposerType(),
        );
    }

    /**
     * Creates the lazy loader of package.
     *
     * @param string             $type
     * @param string             $identifier
     * @param array              $packageData
     * @param VcsDriverInterface $driver
     *
     * @return LazyAssetPackageLoader
     */
    protected function createLazyLoader($type, $identifier, array $packageData, VcsDriverInterface $driver)
    {
        $lazyLoader = new LazyAssetPackageLoader($type, $identifier, $packageData);
        $lazyLoader->setAssetType($this->assetType);
        $lazyLoader->setLoader($this->loader);
        $lazyLoader->setDriver(clone $driver);
        $lazyLoader->setIO($this->io);
        $lazyLoader->setEventDispatcher($this->dispatcher);

        return $lazyLoader;
    }

    /**
     * Pre process the data of package before the conversion to Package instance.
     *
     * @param array $data
     *
     * @return array
     */
    private function preProcessAsset(array $data)
    {
        $vcsRepos = array();

        // keep the name of the main identifier for all packages
        $data['name'] = $this->packageName ?: $data['name'];
        $data = $this->assetType->getPackageConverter()->convert($data, $vcsRepos);

        return (array) $data;
    }

    /**
     * Validates the branch.
     *
     * @param string $branch
     *
     * @return bool
     */
    private function validateBranchAsset($branch)
    {
        try {
            return $this->versionParser->normalizeBranch($branch);
        } catch (\Exception $e) {
            // must return false
        }

        return false;
    }

    /**
     * Validates the tag.
     *
     * @param string $version
     *
     * @return bool
     */
    private function validateTagAsset($version)
    {
        try {
            $version = $this->assetType->getVersionConverter()->convertVersion($version);

            return $this->versionParser->normalize($version);
        } catch (\Exception $e) {
            // must return false
        }

        return false;
    }
}
