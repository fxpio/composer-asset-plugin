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
        $pattern = '/\-[a-z](1,2)[0-9]+|[0-9]+[a-z]+[0-9]+/';

        if ('^' === $prefix) {
            $version = substr($version, 1);
        }

        if (false === strpos($version, '#') && preg_match_all($pattern, $version, $matches, PREG_OFFSET_CAPTURE)) {
            $newVersion = substr($version, 0, $matches[0][0][1]);
            $tag = $matches[0][0][0];

            preg_match_all('/([a-z]{2,})[0-9]+/', $tag, $subMatches, PREG_OFFSET_CAPTURE);

            if ($subMatches[0]) {
                $newVersion .= preg_replace('/[a-z]+/', '-$0', $tag);

            } elseif (0 === strpos($tag, 'b')) {
                $newVersion .= '-beta' . substr($tag, 1);
            }

            $version = $newVersion;
        }

        $version = str_replace('rc', 'RC', $version);
        $version = str_replace('||', ',', $version);

        return $version;
    }
}
