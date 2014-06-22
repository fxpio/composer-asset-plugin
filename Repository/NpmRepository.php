<?php

/**
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Repository;

use Fxp\Composer\AssetPlugin\Assets;

/**
 * NPM repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class NpmRepository extends AbstractAssetsRepository
{
    /**
     * {@inheritdoc}
     */
    protected function getAssetType()
    {
        if (null === $this->assetType) {
            $this->assetType = Assets::createType('npm');
        }

        return parent::getAssetType();
    }

    /**
     * {@inheritdoc}
     */
    protected function getUrl()
    {
        return 'https://registry.npmjs.org';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSlugOfGetPackage()
    {
        return '%package%';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSlugOfSearch()
    {
        return '-/all';
    }

    /**
     * {@inheritDoc}
     */
    public function search($query, $mode = 0)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    protected function createVcsRepositoryConfig(array $data)
    {
        return array(
            'type' => $this->getAssetType()->getName() . '-' . $data['repository']['type'],
            'url'  => $data['repository']['url'],
        );
    }
}
