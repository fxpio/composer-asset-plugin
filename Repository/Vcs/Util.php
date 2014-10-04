<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Repository\Vcs;

use Composer\Cache;
use Composer\Json\JsonFile;
use Composer\Repository\Vcs\VcsDriverInterface;
use Composer\Util\ProcessExecutor;

/**
 * Helper for VCS driver.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class Util
{
    /**
     * Check if the identifier is an SHA.
     *
     * @param string $identifier The identifier
     *
     * @return bool
     */
    public static function isSha($identifier)
    {
        return (bool) preg_match('{[a-f0-9]{40}}i', $identifier);
    }

    /**
     * @param array  $cacheCode  The cache code
     * @param Cache  $cache      The cache filesystem
     * @param string $type       The asset type
     * @param string $identifier The identifier
     *
     * @return array|null
     */
    public static function readCache(array $cacheCode, Cache $cache, $type, $identifier)
    {
        if (array_key_exists($identifier, $cacheCode)) {
            return $cacheCode[$identifier];
        }

        if (self::isSha($identifier)) {
            $res = $cache->read($type . '-' . $identifier);

            if ($res) {
                return JsonFile::parseJson($res);
            }
        }

        return null;
    }

    /**
     * @param Cache  $cache      The cache
     * @param string $type       The asset type
     * @param string $identifier The identifier
     * @param array  $composer   The data composer
     */
    public static function writeCache(Cache $cache, $type, $identifier, array $composer)
    {
        if (self::isSha($identifier)) {
            $cache->write($type . '-' . $identifier, json_encode($composer));
        }
    }

    /**
     * Add time in composer.
     *
     * @param array              $composer    The composer
     * @param string             $resourceKey The composer key
     * @param string             $resource    The resource url
     * @param VcsDriverInterface $driver      The vcs driver
     * @param string             $method      The method for get content
     *
     * @return array The composer
     */
    public static function addComposerTime(array $composer, $resourceKey, $resource, VcsDriverInterface $driver, $method = 'getContents')
    {
        if (!isset($composer['time'])) {
            $ref = new \ReflectionClass($driver);
            $meth = $ref->getMethod($method);
            $meth->setAccessible(true);

            $commit = JsonFile::parseJson((string) $meth->invoke($driver, $resource), $resource);
            $keys = explode('.', $resourceKey);

            while (!empty($keys)) {
                $commit = $commit[$keys[0]];
                array_shift($keys);
            }

            $composer['time'] = $commit;
        }

        return $composer;
    }

    public static function getComposerInformationProcess($identifier, array $config, array &$infoCache)
    {
        $cache = $config['cache'];
        $assetType = $config['asset-type'];
        $infoCache[$identifier] = Util::readCache($infoCache, $cache, $assetType, $identifier);

        if (!isset($infoCache[$identifier])) {
            $resource = $config['resource'];
            $process = $config['process'];
            $cmdGet = $config['cmd-get'];
            $cmdLog = $config['cmd-log'];
            $repoDir = $config['repo-dir'];
            $datetimePrefix = $config['datetime-prefix'];
            $composer = static::doGetComposerInformationProcess($resource, $process, $cmdGet, $cmdLog, $repoDir, $datetimePrefix);

            Util::writeCache($cache, $assetType, $identifier, $composer);
            $infoCache[$identifier] = $composer;
        }

        return $infoCache[$identifier];
    }

    /**
     * Get composer information with Process Executor.
     *
     * @param string          $resource
     * @param ProcessExecutor $process
     * @param string          $cmdGet
     * @param string          $cmdLog
     * @param string          $repoDir
     * @param string          $datetimePrefix
     *
     * @return array The composer
     */
    protected static function doGetComposerInformationProcess($resource, ProcessExecutor $process, $cmdGet, $cmdLog, $repoDir, $datetimePrefix = '')
    {
        $process->execute($cmdGet, $composer, $repoDir);

        if (!trim($composer)) {
            return array('_nonexistent_package' => true);
        }

        $composer = JsonFile::parseJson($composer, $resource);

        return static::addComposerTimeProcess($composer, $process, $cmdLog, $repoDir, $datetimePrefix);
    }

    /**
     * Add time in composer with Process Executor.
     *
     * @param array           $composer
     * @param ProcessExecutor $process
     * @param string          $cmd
     * @param string          $repoDir
     * @param string          $datetimePrefix
     *
     * @return array The composer
     */
    protected static function addComposerTimeProcess(array $composer, ProcessExecutor $process, $cmd, $repoDir, $datetimePrefix = '')
    {
        if (!isset($composer['time'])) {
            $process->execute($cmd, $output, $repoDir);
            $date = new \DateTime($datetimePrefix.trim($output), new \DateTimeZone('UTC'));
            $composer['time'] = $date->format('Y-m-d H:i:s');
        }

        return $composer;
    }
}
