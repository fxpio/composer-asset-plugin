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
     * {@inheritDoc}
     */
    public function initialize()
    {
        /* @var AssetRepositoryManager $arm */
        $arm = $this->repoConfig['asset-repository-manager'];

        if (null !== ($skip = $arm->getConfig()->get('git-skip-update'))) {
            $localUrl = $this->config->get('cache-vcs-dir') . '/' . preg_replace('{[^a-z0-9.]}i', '-', $this->url) . '/';

            // check if local copy exists and if it is a git repository and that modification time is within threshold
            if (is_dir($localUrl) && is_file($localUrl.'/config') && filemtime($localUrl) > strtotime('-'.$skip)) {
                $this->io->write('(<comment>local</comment>) ', false, IOInterface::VERBOSE);
                $this->url = $localUrl;
            } else {
                $this->io->write('(<info>remote</info>) ', false, IOInterface::VERBOSE);
            }
        }
        parent::initialize();
    }
}
