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

use Fxp\Composer\AssetPlugin\Config\Config;
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
    public static function create(AssetRepositoryManager $arm, VcsPackageFilter $filter, Config $config)
    {
        $rm = $arm->getRepositoryManager();
        $registries = $config->getArray('private-bower-registries');

        foreach ($registries as $registryName => $registryUrl) {
            $repoConfig = AssetPlugin::createRepositoryConfig($arm, $filter, $config, $registryName);
            $repoConfig['private-registry-url'] = $registryUrl;

            $rm->setRepositoryClass($registryName, 'Fxp\Composer\AssetPlugin\Repository\BowerPrivateRepository');
            $rm->addRepository($rm->createRepository($registryName, $repoConfig));
        }
    }
}
