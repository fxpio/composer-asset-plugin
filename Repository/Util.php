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

use Composer\DependencyResolver\Pool;
use Composer\Repository\RepositoryManager;

/**
 * Helper for Repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class Util
{
    /**
     * @param RepositoryManager $rm         The repository mamanger
     * @param array             $repos      The list of already repository added (passed by reference)
     * @param string            $name       The name of the new repository
     * @param array             $repoConfig The config of the new repository
     * @param Pool|null         $pool       The pool
     */
    public static function addRepository(RepositoryManager $rm, array &$repos, $name, array $repoConfig, Pool $pool = null)
    {
        if (!isset($repos[$name])) {
            $repo = $rm->createRepository($repoConfig['type'], $repoConfig);
            $repos[$name] = $repo;
            $rm->addRepository($repo);

            if (null !== $pool) {
                $pool->addRepository($repo);
            }
        }
    }
}
