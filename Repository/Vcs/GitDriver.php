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
use Composer\Repository\Vcs\GitDriver as BaseGitDriver;

/**
 * Git vcs driver.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class GitDriver extends BaseGitDriver
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * {@inheritDoc}
     */
    public function getComposerInformation($identifier)
    {
        if (preg_match('{[a-f0-9]{40}}i', $identifier) && $res = $this->cache->read($this->repoConfig['asset-type'] . '-' . $identifier)) {
            $this->infoCache[$identifier] = JsonFile::parseJson($res);
        }

        if (!isset($this->infoCache[$identifier])) {
            $resource = sprintf('%s:%s', escapeshellarg($identifier), $this->repoConfig['filename']);
            $this->process->execute(sprintf('git show %s', $resource), $composer, $this->repoDir);

            if (!trim($composer)) {
                return null;
            }

            $composer = JsonFile::parseJson($composer, $resource);

            if (!isset($composer['time'])) {
                $this->process->execute(sprintf('git log -1 --format=%%at %s', escapeshellarg($identifier)), $output, $this->repoDir);
                $date = new \DateTime('@'.trim($output), new \DateTimeZone('UTC'));
                $composer['time'] = $date->format('Y-m-d H:i:s');
            }

            if (preg_match('{[a-f0-9]{40}}i', $identifier)) {
                $this->cache->write($this->repoConfig['asset-type'] . '-' . $identifier, json_encode($composer));
            }

            $this->infoCache[$identifier] = $composer;
        }

        return $this->infoCache[$identifier];
    }
}
