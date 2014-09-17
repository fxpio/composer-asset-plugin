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
use Fxp\Composer\AssetPlugin\Converter\SemverUtil;

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

    /**
     * Cleans the package name, removing the Composer prefix if present.
     *
     * @param string $name
     *
     * @return string
     */
    public static function cleanPackageName($name)
    {
        if (preg_match('/^[a-z]+\-asset\//', $name, $matches)) {
            $name = substr($name, strlen($matches[0]));
        }

        return $name;
    }

    /**
     * Converts the alias of asset package name by the real asset package name.
     *
     * @param string $name
     *
     * @return string
     */
    public static function convertAliasName($name)
    {
        $pos = strrpos($name, '-');

        if (false !== $pos) {
            $version = substr($name, $pos + 1);

            if (preg_match(SemverUtil::createPattern(''), $version)) {
                return substr($name, 0, $pos);
            }
        }

        return $name;
    }
}
