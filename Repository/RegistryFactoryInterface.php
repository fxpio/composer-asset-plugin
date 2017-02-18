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

/**
 * Interface of repository registry factory.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
interface RegistryFactoryInterface
{
    /**
     * Create the repository registries.
     *
     * @param AssetRepositoryManager $arm    The asset repository manager
     * @param VcsPackageFilter       $filter The vcs package filter
     * @param Config                 $config The plugin config
     */
    public static function create(AssetRepositoryManager $arm, VcsPackageFilter $filter, Config $config);
}
