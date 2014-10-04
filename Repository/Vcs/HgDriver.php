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
        $this->infoCache[$identifier] = Util::readCache($this->infoCache, $this->cache, $this->repoConfig['asset-type'], $identifier);

        if (!isset($this->infoCache[$identifier])) {
            $resource = sprintf('%s %s', ProcessExecutor::escape($identifier), $this->repoConfig['filename']);
            $cmdGet = sprintf('hg cat -r %s', $resource);
            $cmdLog = sprintf('hg log --template "{date|rfc3339date}" -r %s', ProcessExecutor::escape($identifier));
            $composer = Util::getComposerInformationProcess($resource, $this->process, $cmdGet, $cmdLog, $this->repoDir);

            Util::writeCache($this->cache, $this->repoConfig['asset-type'], $identifier, $composer);
            $this->infoCache[$identifier] = $composer;
        }

        return $this->infoCache[$identifier];
    }
}
