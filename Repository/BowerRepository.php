<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Repository;

/**
 * Bower repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class BowerRepository extends AbstractAssetsRepository
{
    protected function getType()
    {
        return 'bower';
    }

    protected function getUrl()
    {
        return 'https://registry.bower.io/packages';
    }

    protected function getPackageUrl()
    {
        return $this->canonicalizeUrl($this->baseUrl.'/%package%');
    }

    protected function getSearchUrl()
    {
        return $this->canonicalizeUrl($this->baseUrl.'/search/%query%');
    }

    protected function createVcsRepositoryConfig(array $data, $registryName = null)
    {
        return array(
            'type' => $this->assetType->getName().'-vcs',
            'url' => $data['url'],
            'name' => $registryName,
        );
    }
}
