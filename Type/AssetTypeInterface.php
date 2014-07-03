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

use Fxp\Composer\AssetPlugin\Converter\VersionConverterInterface;

/**
 * Asset type interface.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
interface AssetTypeInterface
{
    /**
     * Gets the name of asset package mapping.
     *
     * @return string
     */
    public function getName();

    /**
     * Gets the composer vendor name.
     *
     * @return string
     */
    public function getComposerVendorName();

    /**
     * Gets the filename of asset package.
     *
     * @return string
     */
    public function getFilename();

    /**
     * Gets the version converter.
     *
     * @return VersionConverterInterface
     */
    public function getVersionConverter();

    /**
     * Converts the asset data package to composer data package.
     *
     * @param array $data The asset data package
     *
     * @return array The composer data package
     */
    public function convert(array $data);
}
