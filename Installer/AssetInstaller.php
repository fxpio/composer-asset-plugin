<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Installer;

use Composer\Composer;
use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Installer for asset packages.
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
class AssetInstaller extends LibraryInstaller
{
    /**
     * Constructor.
     *
     * @param IOInterface        $io
     * @param Composer           $composer
     * @param AssetTypeInterface $assetType
     * @param Filesystem         $filesystem
     */
    public function __construct(IOInterface $io, Composer $composer, AssetTypeInterface $assetType, Filesystem $filesystem = null)
    {
        parent::__construct($io, $composer, $assetType->getComposerType(), $filesystem);

        $extra = $composer->getPackage()->getExtra();
        if (!empty($extra['asset-installer-paths'][$this->type])) {
            $this->vendorDir = rtrim($extra['asset-installer-paths'][$this->type], '/');
        } else {
            $this->vendorDir = rtrim($this->vendorDir.'/'.$assetType->getComposerVendorName(), '/');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($packageType)
    {
        return $packageType === $this->type;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPackageBasePath(PackageInterface $package)
    {
        $this->initializeVendorDir();

        list(, $name) = explode('/', $package->getPrettyName(), 2);

        return ($this->vendorDir ? $this->vendorDir.'/' : '') . $name;
    }
}
