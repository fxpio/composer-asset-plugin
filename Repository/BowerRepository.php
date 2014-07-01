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

use Fxp\Composer\AssetPlugin\Assets;

/**
 * Bower repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class BowerRepository extends AbstractAssetsRepository
{
    /**
     * {@inheritdoc}
     */
    protected function getAssetType()
    {
        if (null === $this->assetType) {
            $this->assetType = Assets::createType('bower');
        }

        return parent::getAssetType();
    }

    /**
     * {@inheritdoc}
     */
    protected function getUrl()
    {
        return 'https://bower.herokuapp.com/packages';
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
        return 'search/%query%';
    }

    /**
     * {@inheritdoc}
     */
    protected function createVcsRepositoryConfig(array $data)
    {
        return array(
            'type' => $this->getAssetType()->getName() . '-vcs',
            'url'  => $data['url'],
        );
    }
}
