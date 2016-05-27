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

use Composer\Repository\RepositoryManager;
use Fxp\Composer\AssetPlugin\Util\AssetPlugin;

/**
 * Factory of bower private repository registries.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class BowerPrivateRegistryFactory implements RegistryFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public static function create(RepositoryManager $rm, VcsPackageFilter $filter, array $extra)
    {
        if (!array_key_exists('asset-private-bower-registries', $extra)
                || !is_array($extra['asset-private-bower-registries'])) {
            return;
        }

        $registries = $extra['asset-private-bower-registries'];

        foreach ($registries as $registryName => $registryUrl) {
            $config = AssetPlugin::createRepositoryConfig($rm, $filter, $extra, $registryName);
            $config['private-registry-url'] = $registryUrl;

            $rm->setRepositoryClass($registryName, 'Fxp\Composer\AssetPlugin\Repository\BowerPrivateRepository');
            $rm->addRepository($rm->createRepository($registryName, $config));
        }
    }
}
