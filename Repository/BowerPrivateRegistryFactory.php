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

use Composer\Package\RootPackageInterface;
use Fxp\Composer\AssetPlugin\Util\AssetPlugin;
use Fxp\Composer\AssetPlugin\Util\Config;

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
    public static function create(AssetRepositoryManager $arm, VcsPackageFilter $filter, RootPackageInterface $package)
    {
        $rm = $arm->getRepositoryManager();
        $registries = Config::getArray($package, 'private-bower-registries');

        foreach ($registries as $registryName => $registryUrl) {
            $config = AssetPlugin::createRepositoryConfig($arm, $filter, $package, $registryName);
            $config['private-registry-url'] = $registryUrl;

            $rm->setRepositoryClass($registryName, 'Fxp\Composer\AssetPlugin\Repository\BowerPrivateRepository');
            $rm->addRepository($rm->createRepository($registryName, $config));
        }
    }
}
