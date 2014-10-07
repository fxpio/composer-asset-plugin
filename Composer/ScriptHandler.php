<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Composer;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Package\PackageInterface;
use Composer\Script\PackageEvent;
use Fxp\Composer\AssetPlugin\Assets;
use Fxp\Composer\AssetPlugin\Installer\IgnoreFactory;

/**
 * Composer script handler.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class ScriptHandler
{
    /**
     * Remove ignored files of the installed package defined in the root
     * package extra section.
     *
     * @param PackageEvent $event
     */
    public static function deleteIgnoredFiles(PackageEvent $event)
    {
        if (null === $package = static::getLibraryPackage($event->getOperation())) {
            return;
        }

        $section = static::getIgnoreExtraSection();
        $manager = IgnoreFactory::create($event->getComposer(), $package, null, $section);
        $manager->cleanup();
    }

    /**
     * Get the root extra section of igore file patterns for each package.
     *
     * @return string The extra section name
     */
    protected static function getIgnoreExtraSection()
    {
        return 'asset-ignore-files';
    }

    /**
     * Get the library package (not asset package).
     *
     * @param OperationInterface $operation The operation
     *
     * @return PackageInterface|null Return NULL if the package is an asset
     */
    protected static function getLibraryPackage(OperationInterface $operation)
    {
        $package = static::getOperationPackage($operation);

        if ($package && !static::isAsset($package)) {
            return $package;
        }

        return null;
    }

    /**
     * Get the package defined in the composer operation.
     *
     * @param OperationInterface $operation The operation
     *
     * @return PackageInterface|null Return NULL if the operation is not INSTALL or UPDATE
     */
    protected static function getOperationPackage(OperationInterface $operation)
    {
        if ($operation instanceof UpdateOperation) {
            return $operation->getTargetPackage();

        } elseif ($operation instanceof InstallOperation) {
            return $operation->getPackage();
        }

        return null;
    }

    /**
     * Check if the package is a asset package.
     *
     * @param PackageInterface $package The package instance
     *
     * @return bool
     */
    protected static function isAsset(PackageInterface $package)
    {
        foreach (Assets::getTypes() as $type) {
            $type = Assets::createType($type);

            if ($package->getType() === $type->getComposerType()) {
                return true;
            }
        }

        return false;
    }
}
