<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Type;

use Fxp\Composer\AssetPlugin\Converter\NpmPackageConverter;
use Fxp\Composer\AssetPlugin\Converter\PackageConverterInterface;
use Fxp\Composer\AssetPlugin\Converter\VersionConverterInterface;

/**
 * NPM asset type.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class NpmAssetType extends AbstractAssetType
{
    /**
     * Constructor.
     *
     * @param PackageConverterInterface $packageConverter
     * @param VersionConverterInterface $versionConverter
     */
    public function __construct(PackageConverterInterface $packageConverter = null, VersionConverterInterface $versionConverter = null)
    {
        $packageConverter = !$packageConverter ? new NpmPackageConverter($this) : $packageConverter;

        parent::__construct($packageConverter, $versionConverter);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'npm';
    }

    /**
     * {@inheritdoc}
     */
    public function getFilename()
    {
        return 'package.json';
    }
}
