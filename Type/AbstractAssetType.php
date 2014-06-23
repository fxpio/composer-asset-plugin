<?php

/**
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Type;

/**
 * Abstract asset type.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class AbstractAssetType implements AssetTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function getComposerVendorName()
    {
        return $this->getName() . '-asset';
    }

    /**
     * {@inheritdoc}
     */
    public function getFilename()
    {
        return $this->getName() . '.json';
    }

    /**
     * Converts the asset version to composer version.
     *
     * @param string $version
     *
     * @return string
     */
    protected function convertVersion($version)
    {
        $prefix = substr($version, 0, 1);

        if ('^' === $prefix) {
            $version = substr($version, 1);
        }

        $version = str_replace('-', '-beta', $version);
        $version = str_replace('||', ',', $version);

        return $version;
    }
}
