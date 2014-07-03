<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Converter;

/**
 * Converter for bower package to composer package.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class BowerPackageConverter extends AbstractPackageConverter
{
    /**
     * {@inheritdoc}
     */
    public function convert(array $data)
    {
        $assetType = $this->assetType;
        $keys = array(
            'name'        => array('name', function ($value) use ($assetType) {
                return $assetType->getComposerVendorName() . '/' . $value;
            }),
            'type'        => array('type', function () {
                return 'bower-asset-library';
            }),
            'version'     => array('version', function ($value) use ($assetType) {
                return $assetType->getVersionConverter()->convertVersion($value);
            }),
            'description' => 'description',
            'keywords'    => 'keywords',
            'license'     => 'license',
            'bin'         => 'bin',
        );
        $dependencies = array(
            'dependencies'    => 'require',
            'devDependencies' => 'require-dev',
        );
        $extras = array(
            'main'    => 'bower-asset-main',
            'ignore'  => 'bower-asset-ignore',
            'private' => 'bower-asset-private',
        );

        return $this->convertData($data, $keys, $dependencies, $extras);
    }
}
