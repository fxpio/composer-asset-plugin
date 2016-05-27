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
     * @param RepositoryManager $rm     The repository manager
     * @param VcsPackageFilter  $filter The vcs package filter
     * @param array             $extra  The composer extra
     */
    public static function create(RepositoryManager $rm, VcsPackageFilter $filter, array $extra);
}
