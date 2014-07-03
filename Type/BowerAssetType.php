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

/**
 * Bower asset type.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class BowerAssetType extends AbstractAssetType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'bower';
    }

    /**
     * {@inheritdoc}
     */
    public function convert(array $data)
    {
        $package = array(
            'name'    => $this->getComposerVendorName() . '/' . $data['name'],
            'type'    => "bower-asset-library",
            'version' => $this->getVersionConverter()->convertVersion($data['version']),
        );

        if (isset($data['description'])) {
            $package['description'] = $data['description'];
        }

        if (isset($data['keywords'])) {
            $package['keywords'] = $data['keywords'];
        }

        if (isset($data['license'])) {
            $package['license'] = $data['license'];
        }

        if (isset($data['dependencies'])) {
            $package['require'] = array();

            foreach ($data['dependencies'] as $dependency => $version) {
                $version = $this->getVersionConverter()->convertRange($version);
                $package['require'][$this->getComposerVendorName() . '/' . $dependency] = $version;
            }
        }

        if (isset($data['devDependencies'])) {
            $package['require-dev'] = array();

            foreach ($data['devDependencies'] as $dependency => $version) {
                $version = $this->getVersionConverter()->convertRange($version);
                $package['require-dev'][$this->getComposerVendorName() . '/' . $dependency] = $version;
            }
        }

        if (isset($data['bin'])) {
            $package['bin'] = $data['bin'];
        }

        $extra = array();

        if (isset($data['main'])) {
            $extra['bower-asset-main'] = $data['main'];
        }

        if (isset($data['ignore'])) {
            $extra['bower-asset-ignore'] = $data['ignore'];
        }

        if (isset($data['private'])) {
            $extra['bower-asset-private'] = $data['private'];
        }

        if (count($extra) > 0) {
            $package['extra'] = $extra;
        }

        return $package;
    }
}
