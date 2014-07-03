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
use Composer\Downloader\TransportException;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Loader\InvalidPackageException;
use Composer\Package\Loader\ValidatingArrayLoader;
use Composer\Package\Version\VersionParser;
use Composer\Repository\InvalidRepositoryException;
use Composer\Repository\Vcs\VcsDriverInterface;
use Composer\Repository\VcsRepository;
use Fxp\Composer\AssetPlugin\Assets;
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

        parent::__construct($repoConfig, $io, $config, $dispatcher, $drivers);
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        $this->packages = array();

        $verbose = $this->verbose;
        $assetType = $this->assetType->getName();
        $prefixPackage = $this->assetType->getComposerVendorName() . '/';
        $filename = $this->assetType->getFilename();

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
            if ($verbose) {
                $this->io->write('<error>Skipped parsing '.$driver->getRootIdentifier().', '.$e->getMessage().'</error>');
            }
        }

        foreach ($driver->getTags() as $tag => $identifier) {
            $msg = 'Reading ' . $filename . ' of <info>' . $prefixPackage . ($this->packageName ?: $this->url) . '</info> (<comment>' . $tag . '</comment>)';
            if ($verbose) {
                $this->io->write($msg);
            } else {
                $this->io->overwrite($msg, false);
            }

            // strip the release- prefix from tags if present
            $tag = str_replace('release-', '', $tag);

            if (!$parsedTag = $this->validateTag($tag)) {
                if ($verbose) {
                    $this->io->write('<warning>Skipped tag '.$tag.', invalid tag name</warning>');
                }
                continue;
            }

            try {
                if (!$data = $driver->getComposerInformation($identifier)) {
                    if ($verbose) {
                        $this->io->write('<warning>Skipped tag '.$tag.', no ' . $assetType . ' file</warning>');
                    }
                    continue;
                }

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
                $data['version'] = preg_replace('{[.-]?dev$}i', '', $data['version']);
                $data['version_normalized'] = preg_replace('{(^dev-|[.-]?dev$)}i', '', $data['version_normalized']);

                // broken package, version doesn't match tag
                if ($data['version_normalized'] !== $parsedTag) {
                    if ($verbose) {
                        $this->io->write('<warning>Skipped tag '.$tag.', tag ('.$parsedTag.') does not match version ('.$data['version_normalized'].') in ' . $filename . '</warning>');
                    }
                    continue;
                }

                if ($verbose) {
                    $this->io->write('Importing tag '.$tag.' ('.$data['version_normalized'].')');
                }

                $this->addPackage($this->loader->load($this->preProcess($driver, $data, $identifier)));
            } catch (\Exception $e) {
                if ($verbose) {
                    $this->io->write('<warning>Skipped tag '.$tag.', '.($e instanceof TransportException ? 'no ' . $assetType . ' file was found' : $e->getMessage()).'</warning>');
                }
                continue;
            }
        }

        if (!$verbose) {
            $this->io->overwrite('', false);
        }

        foreach ($driver->getBranches() as $branch => $identifier) {
            $msg = 'Reading ' . $filename . ' of <info>' . $prefixPackage . ($this->packageName ?: $this->url) . '</info> (<comment>' . $branch . '</comment>)';
            if ($verbose) {
                $this->io->write($msg);
            } else {
                $this->io->overwrite($msg, false);
            }

            if (!$parsedBranch = $this->validateBranch($branch)) {
                if ($verbose) {
                    $this->io->write('<warning>Skipped branch '.$branch.', invalid name</warning>');
                }
                continue;
            }

            try {
                if (!$data = $driver->getComposerInformation($identifier)) {
                    if ($verbose) {
                        $this->io->write('<warning>Skipped branch '.$branch.', no ' . $assetType . ' file</warning>');
                    }
                    continue;
                }

                // branches are always auto-versioned, read value from branch name
                $data['version'] = $branch;
                $data['version_normalized'] = $parsedBranch;

                // make sure branch packages have a dev flag
                if ('dev-' === substr($parsedBranch, 0, 4) || '9999999-dev' === $parsedBranch) {
                    $data['version'] = 'dev-' . $data['version'];
                } else {
                    $data['version'] = preg_replace('{(\.9{7})+}', '.x', $parsedBranch);
                }

                if ($verbose) {
                    $this->io->write('Importing branch '.$branch.' ('.$data['version'].')');
                }

                $packageData = $this->preProcess($driver, $data, $identifier);
                $package = $this->loader->load($packageData);
                if ($this->loader instanceof ValidatingArrayLoader && $this->loader->getWarnings()) {
                    throw new InvalidPackageException($this->loader->getErrors(), $this->loader->getWarnings(), $packageData);
                }
                $this->addPackage($package);
            } catch (TransportException $e) {
                if ($verbose) {
                    $this->io->write('<warning>Skipped branch '.$branch.', no ' . $assetType . ' file was found</warning>');
                }
                continue;
            } catch (\Exception $e) {
                if (!$verbose) {
                    $this->io->write('');
                }
                $this->branchErrorOccurred = true;
                $this->io->write('<error>Skipped branch '.$branch.', '.$e->getMessage().'</error>');
                $this->io->write('');
                continue;
            }
        }
        $driver->cleanup();

        if (!$verbose) {
            $this->io->overwrite('', false);
        }

        if (!$this->getPackages()) {
            throw new InvalidRepositoryException('No valid ' . $filename . ' was found in any branch or tag of '.$this->url.', could not load a package from it.');
        }
    }

    /**
     * Pre process the data of package before the conversion to Package instance.
     *
     * @param VcsDriverInterface $driver
     * @param array              $data
     * @param string             $identifier
     *
     * @return array
     */
    private function preProcess(VcsDriverInterface $driver, array $data, $identifier)
    {
        // keep the name of the main identifier for all packages
        $data['name'] = $this->packageName ?: $data['name'];
        $data = $this->assetType->getPackageConverter()->convert($data);

        if (!isset($data['dist'])) {
            $data['dist'] = $driver->getDist($identifier);
        }
        if (!isset($data['source'])) {
            $data['source'] = $driver->getSource($identifier);
        }

        return $data;
    }

    /**
     * Validates the branch.
     *
     * @param string $branch
     *
     * @return bool
     */
    private function validateBranch($branch)
    {
        try {
            return $this->versionParser->normalizeBranch($branch);
        } catch (\Exception $e) {
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
    private function validateTag($version)
    {
        try {
            $version = $this->assetType->getVersionConverter()->convertVersion($version);

            return $this->versionParser->normalize($version);
        } catch (\Exception $e) {
        }

        return false;
    }
}
