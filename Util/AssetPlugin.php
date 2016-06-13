<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Util;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use Composer\Repository\RepositoryManager;
use Composer\Package\PackageInterface;
use Fxp\Composer\AssetPlugin\Assets;
use Fxp\Composer\AssetPlugin\Installer\AssetInstaller;
use Fxp\Composer\AssetPlugin\Installer\BowerInstaller;
use Fxp\Composer\AssetPlugin\Repository\Util;
use Fxp\Composer\AssetPlugin\Repository\VcsPackageFilter;

/**
 * Helper for FxpAssetPlugin.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class AssetPlugin
{
    /**
     * Adds asset installers.
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public static function addInstallers(Composer $composer, IOInterface $io)
    {
        $im = $composer->getInstallationManager();

        $im->addInstaller(new BowerInstaller($io, $composer, Assets::createType('bower')));
        $im->addInstaller(new AssetInstaller($io, $composer, Assets::createType('npm')));
    }

    /**
     * Creates the asset options.
     *
     * @param array  $extra     The composer extra section of asset options
     * @param string $assetType The asset type
     *
     * @return array The asset registry options
     */
    public static function createAssetOptions(array $extra, $assetType)
    {
        $options = array();

        foreach ($extra as $key => $value) {
            if (0 === strpos($key, $assetType.'-')) {
                $key = substr($key, strlen($assetType) + 1);
                $options[$key] = $value;
            }
        }

        return $options;
    }

    /**
     * Create the repository config.
     *
     * @param RepositoryManager $rm        The repository manager
     * @param VcsPackageFilter  $filter    The vcs package filter
     * @param array             $extra     The composer extra
     * @param string            $assetType The asset type
     *
     * @return array
     */
    public static function createRepositoryConfig(RepositoryManager $rm, VcsPackageFilter $filter, array $extra, $assetType)
    {
        $opts = Util::getArrayValue($extra, 'asset-registry-options', array());

        return array(
            'repository-manager' => $rm,
            'vcs-package-filter' => $filter,
            'asset-options' => static::createAssetOptions($opts, $assetType),
            'vcs-driver-options' => Util::getArrayValue($extra, 'asset-vcs-driver-options', array()),
        );
    }

    /**
     * Adds asset registry repositories.
     *
     * @param RepositoryManager $rm
     * @param VcsPackageFilter  $filter
     * @param array             $extra
     */
    public static function addRegistryRepositories(RepositoryManager $rm, VcsPackageFilter $filter, array $extra)
    {
        foreach (Assets::getRegistryFactories() as $registryType => $factoryClass) {
            $ref = new \ReflectionClass($factoryClass);

            if ($ref->implementsInterface('Fxp\Composer\AssetPlugin\Repository\RegistryFactoryInterface')) {
                call_user_func(array($factoryClass, 'create'), $rm, $filter, $extra);
            }
        }
    }

    /**
     * Sets vcs type repositories.
     *
     * @param RepositoryManager $rm
     */
    public static function setVcsTypeRepositories(RepositoryManager $rm)
    {
        foreach (Assets::getTypes() as $assetType) {
            foreach (Assets::getVcsRepositoryDrivers() as $driverType => $repositoryClass) {
                $rm->setRepositoryClass($assetType.'-'.$driverType, $repositoryClass);
            }
        }
    }

    /**
     * Adds the main file definitions from the root package.
     *
     * @param Composer         $composer
     * @param PackageInterface $package
     * @param string           $section
     *
     * @return PackageInterface
     */
    public static function addMainFiles(Composer $composer, PackageInterface $package, $section = 'asset-main-files')
    {
        if ($package instanceof Package) {
            $packageExtra = $package->getExtra();

            $extra = $composer->getPackage()->getExtra();
            if (isset($extra[$section])) {
                foreach ($extra[$section] as $packageName => $files) {
                    if ($packageName === $package->getName()) {
                        $packageExtra['bower-asset-main'] = $files;
                        break;
                    }
                }
            }
            $package->setExtra($packageExtra);
        }

        return $package;
    }
}
