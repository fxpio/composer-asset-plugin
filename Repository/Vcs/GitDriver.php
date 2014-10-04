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
        $resource = sprintf('%s:%s', escapeshellarg($identifier), $this->repoConfig['filename']);
        $config = array(
            'cache'           => $this->cache,
            'asset-type'      => $this->repoConfig['asset-type'],
            'resource'        => $resource,
            'process'         => $this->process,
            'cmd-get'         => sprintf('git show %s', $resource),
            'cmd-log'         => sprintf('git log -1 --format=%%at %s', escapeshellarg($identifier)),
            'repo-dir'        => $this->repoDir,
            'datetime-prefix' => '@',
        );

        return Util::getComposerInformationProcess($identifier, $config, $this->infoCache);
    }
}
