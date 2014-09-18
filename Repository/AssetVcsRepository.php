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
use Composer\Package\AliasPackage;
use Composer\Package\CompletePackageInterface;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Loader\LoaderInterface;
use Composer\Package\Version\VersionParser;
use Composer\Repository\InvalidRepositoryException;
use Composer\Repository\Vcs\VcsDriverInterface;
use Composer\Repository\VcsRepository;
use Fxp\Composer\AssetPlugin\Assets;
use Fxp\Composer\AssetPlugin\Converter\SemverConverter;
use Fxp\Composer\AssetPlugin\Package\LazyCompletePackage;
use Fxp\Composer\AssetPlugin\Package\Loader\LazyAssetPackageLoader;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;
use Fxp\Composer\AssetPlugin\Util\Validator;

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
     * @var string
     */
    protected $rootPackageVersion;

    /**
     * @var array|null
     */
    protected $rootData;

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
     * Gets the package name of this repository.
     *
     * @return string
     */
    public function getComposerPackageName()
    {
        if (null === $this->packages) {
            $this->initialize();
        }

        return $this->assetType->formatComposerName($this->packageName);
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        $this->packages = array();
        $this->packageName = isset($this->repoConfig['name']) ? Util::cleanPackageName($this->repoConfig['name']) : null;
        $driver = $this->initDriver();

        $this->initLoader();
        $this->initRootIdentifier($driver);
        $this->initTags($driver);
        $this->initBranches($driver);
        $driver->cleanup();

        if (!$this->getPackages()) {
            throw new InvalidRepositoryException('No valid ' . $this->assetType->getFilename() . ' was found in any branch or tag of '.$this->url.', could not load a package from it.');
        }
    }

    /**
     * Initializes the driver.
     *
     * @return VcsDriverInterface
     *
     * @throws \InvalidArgumentException When not driver found.
     */
    protected function initDriver()
    {
        $driver = $this->getDriver();
        if (!$driver) {
            throw new \InvalidArgumentException('No driver found to handle Asset VCS repository '.$this->url);
        }

        return $driver;
    }

    /**
     * Initializes the version parser and loader.
     */
    protected function initLoader()
    {
        $this->versionParser = new VersionParser();

        if (!$this->loader) {
            $this->loader = new ArrayLoader($this->versionParser);
        }
    }

    /**
     * Initializes the root identifier.
     *
     * @param VcsDriverInterface $driver
     */
    protected function initRootIdentifier(VcsDriverInterface $driver)
    {
        try {
            if ($driver->hasComposerFile($driver->getRootIdentifier())) {
                $data = $driver->getComposerInformation($driver->getRootIdentifier());
                $sc = new SemverConverter();
                $this->rootPackageVersion = !empty($data['version']) ? $sc->convertVersion($data['version']) : null;
                $this->rootData = $data;

                if (null === $this->packageName) {
                    $this->packageName = !empty($data['name']) ? $data['name'] : null;
                }
            }
        } catch (\Exception $e) {
            if ($this->verbose) {
                $this->io->write('<error>Skipped parsing '.$driver->getRootIdentifier().', '.$e->getMessage().'</error>');
            }
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
        $packageClass = 'Fxp\Composer\AssetPlugin\Package\LazyCompletePackage';

        foreach ($driver->getTags() as $tag => $identifier) {
            $packageName = $this->createPackageName();

            // strip the release- prefix from tags if present
            $tag = str_replace('release-', '', $tag);

            if (!$parsedTag = Validator::validateTag($tag, $this->assetType, $this->versionParser)) {
                if ($verbose) {
                    $this->io->write('<warning>Skipped tag '.$tag.', invalid tag name</warning>');
                }
                continue;
            }

            $data = $this->createMockOfPackageConfig($packageName, $tag);
            $data['version'] = $this->assetType->getVersionConverter()->convertVersion($tag);
            $data['version_normalized'] = $parsedTag;

            // make sure tag packages have no -dev flag
            $data['version'] = preg_replace('{[.-]?dev$}i', '', (string) $data['version']);
            $data['version_normalized'] = preg_replace('{(^dev-|[.-]?dev$)}i', '', (string) $data['version_normalized']);

            $packageData = $this->preProcessAsset($data);
            $package = $this->loader->load($packageData, $packageClass);
            $lazyLoader = $this->createLazyLoader('tag', $identifier, $packageData, $driver);
            /* @var LazyCompletePackage $package */
            $package->setLoader($lazyLoader);
            $this->addPackage($package);
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
        foreach ($driver->getBranches() as $branch => $identifier) {
            if (is_array($this->rootData) && $branch === $driver->getRootIdentifier()) {
                $this->preInitBranchPackage($driver, $this->rootData, $branch, $identifier);
                continue;
            }

            $this->preInitBranchLazyPackage($driver, $branch, $identifier);
        }

        if (!$this->verbose) {
            $this->io->overwrite('', false);
        }
    }

    /**
     * Pre inits the branch of complete package.
     *
     * @param VcsDriverInterface $driver     The vcs driver
     * @param array              $data       The asset package data
     * @param string             $branch     The branch name
     * @param string             $identifier The branch identifier
     */
    protected function preInitBranchPackage(VcsDriverInterface $driver, array $data, $branch, $identifier)
    {
        $packageName = $this->createPackageName();
        $data = array_merge($this->createMockOfPackageConfig($packageName, $branch), $data);
        $data = $this->preProcessAsset($data);
        $data['version'] = $branch;
        $data = $this->configureBranchPackage($branch, $data);

        if (!isset($data['dist'])) {
            $data['dist'] = $driver->getDist($identifier);
        }
        if (!isset($data['source'])) {
            $data['source'] = $driver->getSource($identifier);
        }

        $loader = new ArrayLoader();
        $package = $loader->load($data);
        $package = $this->includeBranchAlias($driver, $package, $branch);
        $this->addPackage($package);

    }

    /**
     * Pre inits the branch of lazy package.
     *
     * @param VcsDriverInterface $driver     The vcs driver
     * @param string             $branch     The branch name
     * @param string             $identifier The branch identifier
     */
    protected function preInitBranchLazyPackage(VcsDriverInterface $driver, $branch, $identifier)
    {
        $packageName = $this->createPackageName();
        $data = $this->createMockOfPackageConfig($packageName, $branch);
        $data = $this->configureBranchPackage($branch, $data);

        $this->initBranchLazyPackage($driver, $data, $branch, $identifier);
    }

    /**
     * Configures the package of branch.
     *
     * @param string $branch The branch name
     * @param array  $data   The data
     *
     * @return array
     */
    protected function configureBranchPackage($branch, array $data)
    {
        $parsedBranch = $this->versionParser->normalizeBranch($branch);
        $data['version_normalized'] = $parsedBranch;

        // make sure branch packages have a dev flag
        if ('dev-' === substr((string) $parsedBranch, 0, 4) || '9999999-dev' === $parsedBranch) {
            $data['version'] = 'dev-' . $data['version'];
        } else {
            $data['version'] = preg_replace('{(\.9{7})+}', '.x', (string) $parsedBranch);
        }

        return $data;
    }

    /**
     * Inits the branch of lazy package.
     *
     * @param VcsDriverInterface $driver     The vcs driver
     * @param array              $data       The package data
     * @param string             $branch     The branch name
     * @param string             $identifier The branch identifier
     */
    protected function initBranchLazyPackage(VcsDriverInterface $driver, array $data, $branch, $identifier)
    {
        $packageClass = 'Fxp\Composer\AssetPlugin\Package\LazyCompletePackage';
        $packageData = $this->preProcessAsset($data);
        /* @var LazyCompletePackage $package */
        $package = $this->loader->load($packageData, $packageClass);
        $lazyLoader = $this->createLazyLoader('branch', $identifier, $packageData, $driver);
        $package->setLoader($lazyLoader);
        $package = $this->includeBranchAlias($driver, $package, $branch);

        $this->addPackage($package);
    }

    /**
     * Include the package in the alias package if the branch is a root branch
     * identifier and having a package version.
     *
     * @param VcsDriverInterface       $driver  The vcs driver
     * @param CompletePackageInterface $package The package instance
     * @param string                   $branch  The branch name
     *
     * @return CompletePackageInterface|AliasPackage
     */
    protected function includeBranchAlias(VcsDriverInterface $driver, CompletePackageInterface $package, $branch)
    {
        if (null !== $this->rootPackageVersion && $branch === $driver->getRootIdentifier()) {
            $aliasNormalized = $this->versionParser->normalize($this->rootPackageVersion);
            $package = new AliasPackage($package, $aliasNormalized, $this->rootPackageVersion);
        }

        return $package;
    }

    /**
     * Creates the package name with the composer prefix and the asset package name,
     * or only with the URL.
     *
     * @return string The package name
     */
    protected function createPackageName()
    {
        if (null === $this->packageName) {
            return $this->url;
        }

        return sprintf('%s/%s', $this->assetType->getComposerVendorName(), $this->packageName);
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
}
