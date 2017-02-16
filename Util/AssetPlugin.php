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
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Repository\RepositoryManager;
use Fxp\Composer\AssetPlugin\Assets;
use Fxp\Composer\AssetPlugin\Installer\AssetInstaller;
use Fxp\Composer\AssetPlugin\Installer\BowerInstaller;
use Fxp\Composer\AssetPlugin\Repository\AssetRepositoryManager;
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
     * @param array  $config    The composer config section of asset options
     * @param string $assetType The asset type
     *
     * @return array The asset registry options
     */
    public static function createAssetOptions(array $config, $assetType)
    {
        $options = array();

        foreach ($config as $key => $value) {
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
     * @param AssetRepositoryManager $arm       The asset repository manager
     * @param VcsPackageFilter       $filter    The vcs package filter
     * @param RootPackageInterface   $package   The root package
     * @param string                 $assetType The asset type
     *
     * @return array
     */
    public static function createRepositoryConfig(AssetRepositoryManager $arm, VcsPackageFilter $filter, RootPackageInterface $package, $assetType)
    {
        return array(
            'asset-repository-manager' => $arm,
            'vcs-package-filter' => $filter,
            'asset-options' => static::createAssetOptions(Config::getArray($package, 'registry-options'), $assetType),
            'vcs-driver-options' => Config::getArray($package, 'vcs-driver-options'),
        );
    }

    /**
     * Adds asset registry repositories.
     *
     * @param AssetRepositoryManager $arm
     * @param VcsPackageFilter       $filter
     * @param RootPackageInterface   $package
     */
    public static function addRegistryRepositories(AssetRepositoryManager $arm, VcsPackageFilter $filter, RootPackageInterface $package)
    {
        foreach (Assets::getRegistryFactories() as $registryType => $factoryClass) {
            $ref = new \ReflectionClass($factoryClass);

            if ($ref->implementsInterface('Fxp\Composer\AssetPlugin\Repository\RegistryFactoryInterface')) {
                call_user_func(array($factoryClass, 'create'), $arm, $filter, $package);
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
    public static function addMainFiles(Composer $composer, PackageInterface $package, $section = 'main-files')
    {
        if ($package instanceof Package) {
            $packageExtra = $package->getExtra();
            $rootMainFiles = Config::getArray($composer->getPackage(), $section);

            foreach ($rootMainFiles as $packageName => $files) {
                if ($packageName === $package->getName()) {
                    $packageExtra['bower-asset-main'] = $files;
                    break;
                }
            }

            $package->setExtra($packageExtra);
        }

        return $package;
    }
}
