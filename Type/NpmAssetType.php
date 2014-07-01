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
 * NPM asset type.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class NpmAssetType extends AbstractAssetType
{
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

    /**
     * {@inheritdoc}
     */
    public function convert(array $data)
    {
        $package = array(
            'name'    => $this->getComposerVendorName() . '/' . $data['name'],
            'type'    => "npm-asset-library",
            'version' => $this->convertVersion($data['version']),
        );

        if (isset($data['description'])) {
            $package['description'] = $data['description'];
        }

        if (isset($data['keywords'])) {
            $package['keywords'] = $data['keywords'];
        }

        if (isset($data['homepage'])) {
            $package['homepage'] = $data['homepage'];
        }

        if (isset($data['license'])) {
            $package['license'] = $data['license'];
        }

        $authors = array();

        if (isset($data['author'])) {
            $authors[] = $data['author'];
        }

        if (isset($data['contributors'])) {
            foreach ($data['contributors'] as $contributor) {
                $authors[] = $contributor;
            }
        }

        if (count($authors) > 0) {
            $package['authors'] = $authors;
        }

        if (isset($data['dependencies'])) {
            $package['require'] = array();

            foreach ($data['dependencies'] as $dependency => $version) {
                $version = $this->convertVersion($version);
                $package['require'][$this->getComposerVendorName() . '/' . $dependency] = $version;
            }
        }

        if (isset($data['devDependencies'])) {
            $package['require-dev'] = array();

            foreach ($data['devDependencies'] as $dependency => $version) {
                $version = $this->convertVersion($version);
                $package['require-dev'][$this->getComposerVendorName() . '/' . $dependency] = $version;
            }
        }

        if (isset($data['bin'])) {
            $package['bin'] = $data['bin'];
        }

        $extra = array();

        if (isset($data['bugs'])) {
            $extra['npm-asset-bugs'] = $data['bugs'];
        }

        if (isset($data['files'])) {
            $extra['npm-asset-files'] = $data['files'];
        }

        if (isset($data['main'])) {
            $extra['npm-asset-main'] = $data['main'];
        }

        if (isset($data['man'])) {
            $extra['npm-asset-man'] = $data['man'];
        }

        if (isset($data['directories'])) {
            $extra['npm-asset-directories'] = $data['directories'];
        }

        if (isset($data['repository'])) {
            $extra['npm-asset-repository'] = $data['repository'];
        }

        if (isset($data['scripts'])) {
            $extra['npm-asset-scripts'] = $data['scripts'];
        }

        if (isset($data['config'])) {
            $extra['npm-asset-config'] = $data['config'];
        }

        if (isset($data['bundledDependencies'])) {
            $extra['npm-asset-bundled-dependencies'] = $data['bundledDependencies'];
        }

        if (isset($data['optionalDependencies'])) {
            $extra['npm-asset-optional-dependencies'] = $data['optionalDependencies'];
        }

        if (isset($data['engines'])) {
            $extra['npm-asset-engines'] = $data['engines'];
        }

        if (isset($data['engineStrict'])) {
            $extra['npm-asset-engine-strict'] = $data['engineStrict'];
        }

        if (isset($data['os'])) {
            $extra['npm-asset-os'] = $data['os'];
        }

        if (isset($data['cpu'])) {
            $extra['npm-asset-cpu'] = $data['cpu'];
        }

        if (isset($data['preferGlobal'])) {
            $extra['npm-asset-prefer-global'] = $data['preferGlobal'];
        }

        if (isset($data['private'])) {
            $extra['npm-asset-private'] = $data['private'];
        }

        if (isset($data['publishConfig'])) {
            $extra['npm-asset-publish-config'] = $data['publishConfig'];
        }

        if (count($extra) > 0) {
            $package['extra'] = $extra;
        }

        return $package;
    }
}
