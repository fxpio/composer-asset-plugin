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
use Composer\Repository\Vcs\HgDriver as BaseHgDriver;
use Composer\Util\Filesystem;
use Composer\Util\ProcessExecutor;

/**
 * Mercurial vcs driver.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class HgDriver extends BaseHgDriver
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * {@inheritDoc}
     */
    public function initialize()
    {
        parent::initialize();

        $cacheUrl = Filesystem::isLocalPath($this->url)
            ? realpath($this->url)
            : $this->url;

        $this->cache = new Cache($this->io, $this->config->get('cache-repo-dir').'/'.preg_replace('{[^a-z0-9.]}i', '-', $cacheUrl));
    }

    /**
     * {@inheritDoc}
     */
    public function getComposerInformation($identifier)
    {
        $resource = sprintf('%s %s', ProcessExecutor::escape($identifier), $this->repoConfig['filename']);
        $config = array(
            'cache'           => $this->cache,
            'asset-type'      => $this->repoConfig['asset-type'],
            'resource'        => $resource,
            'process'         => $this->process,
            'cmd-get'         => sprintf('hg cat -r %s', $resource),
            'cmd-log'         => sprintf('hg log --template "{date|rfc3339date}" -r %s', ProcessExecutor::escape($identifier)),
            'repo-dir'        => $this->repoDir,
            'datetime-prefix' => '',
        );

        return Util::getComposerInformationProcess($identifier, $config, $this->infoCache);
    }
}
