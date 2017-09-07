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
use Composer\IO\IOInterface;
use Composer\Repository\Vcs\GitDriver as BaseGitDriver;
use Composer\Util\Filesystem;
use Composer\Util\Git as GitUtil;
use Fxp\Composer\AssetPlugin\Repository\AssetRepositoryManager;

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
     * {@inheritdoc}
     */
    public function getComposerInformation($identifier)
    {
        $resource = sprintf('%s:%s', escapeshellarg($identifier), $this->repoConfig['filename']);

        return ProcessUtil::getComposerInformation($this->cache, $this->infoCache, $this->repoConfig['asset-type'], $this->process, $identifier, $resource, sprintf('git show %s', $resource), sprintf('git log -1 --format=%%at %s', escapeshellarg($identifier)), $this->repoDir, '@');
    }

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        /* @var AssetRepositoryManager $arm */
        $arm = $this->repoConfig['asset-repository-manager'];
        $skipSync = false;

        if (null !== ($skip = $arm->getConfig()->get('git-skip-update'))) {
            $localUrl = $this->config->get('cache-vcs-dir').'/'.preg_replace('{[^a-z0-9.]}i', '-', $this->url).'/';
            // check if local copy exists and if it is a git repository and that modification time is within threshold
            if (is_dir($localUrl) && is_file($localUrl.'/config') && filemtime($localUrl) > strtotime('-'.$skip)) {
                $skipSync = true;
                $this->io->write('(<comment>skip update</comment>) ', false, IOInterface::VERBOSE);
            }
        }

        $cacheUrl = Filesystem::isLocalPath($this->url)
            ? $this->initializeLocalPath() : $this->initializeRemotePath($skipSync);
        $this->getTags();
        $this->getBranches();
        $this->cache = new Cache($this->io, $this->config->get('cache-repo-dir').'/'.preg_replace('{[^a-z0-9.]}i', '-', $cacheUrl));
    }

    /**
     * Initialize the local path.
     *
     * @return string
     */
    private function initializeLocalPath()
    {
        $this->url = preg_replace('{[\\/]\.git/?$}', '', $this->url);
        $this->repoDir = $this->url;

        return realpath($this->url);
    }

    /**
     * Initialize the remote path.
     *
     * @param bool $skipSync Check if sync must be skipped
     *
     * @return string
     */
    private function initializeRemotePath($skipSync)
    {
        $this->repoDir = $this->config->get('cache-vcs-dir').'/'.preg_replace('{[^a-z0-9.]}i', '-', $this->url).'/';

        GitUtil::cleanEnv();

        $fs = new Filesystem();
        $fs->ensureDirectoryExists(dirname($this->repoDir));

        if (!is_writable(dirname($this->repoDir))) {
            throw new \RuntimeException('Can not clone '.$this->url.' to access package information. The "'.dirname($this->repoDir).'" directory is not writable by the current user.');
        }

        if (preg_match('{^ssh://[^@]+@[^:]+:[^0-9]+}', $this->url)) {
            throw new \InvalidArgumentException('The source URL '.$this->url.' is invalid, ssh URLs should have a port number after ":".'."\n".'Use ssh://git@example.com:22/path or just git@example.com:path if you do not want to provide a password or custom port.');
        }

        $gitUtil = new GitUtil($this->io, $this->config, $this->process, $fs);
        // patched line, sync from local dir without modifying url
        if (!$skipSync && !$gitUtil->syncMirror($this->url, $this->repoDir)) {
            $this->io->writeError('<error>Failed to update '.$this->url.', package information from this repository may be outdated</error>');
        }

        return $this->url;
    }
}
