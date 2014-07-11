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
use Composer\Downloader\TransportException;
use Composer\Json\JsonFile;
use Composer\Repository\Vcs\GitHubDriver as BaseGitHubDriver;

/**
 * GitHub vcs driver.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class GitHubDriver extends BaseGitHubDriver
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
        if ($this->gitDriver) {
            return $this->gitDriver->getComposerInformation($identifier);
        }

        if (preg_match('{[a-f0-9]{40}}i', $identifier) && $res = $this->cache->read($this->repoConfig['asset-type'] . '-' . $identifier)) {
            $this->infoCache[$identifier] = JsonFile::parseJson($res);
        }

        if (!isset($this->infoCache[$identifier])) {
            $notFoundRetries = 2;
            $resource = null;
            $composer = null;
            while ($notFoundRetries) {
                try {
                    $resource = $this->getApiUrl() . '/repos/'.$this->owner.'/'.$this->repository.'/contents/' . $this->repoConfig['filename'] . '?ref='.urlencode($identifier);
                    $composer = JsonFile::parseJson($this->getContents($resource));
                    if (empty($composer['content']) || $composer['encoding'] !== 'base64' || !($composer = base64_decode($composer['content']))) {
                        throw new \RuntimeException('Could not retrieve ' . $this->repoConfig['filename'] . ' from '.$resource);
                    }
                    break;
                } catch (TransportException $e) {
                    if (404 !== $e->getCode()) {
                        throw $e;
                    }

                    // retry fetching if github returns a 404 since they happen randomly
                    $notFoundRetries--;
                    $composer = false;
                }
            }

            if ($composer) {
                $composer = JsonFile::parseJson($composer, $resource);

                if (!isset($composer['time'])) {
                    $resource = $this->getApiUrl() . '/repos/'.$this->owner.'/'.$this->repository.'/commits/'.urlencode($identifier);
                    $commit = JsonFile::parseJson($this->getContents($resource), $resource);
                    $composer['time'] = $commit['commit']['committer']['date'];
                }
                if (!isset($composer['support']['source'])) {
                    $label = array_search($identifier, $this->getTags()) ?: array_search($identifier, $this->getBranches()) ?: $identifier;
                    $composer['support']['source'] = sprintf('https://%s/%s/%s/tree/%s', $this->originUrl, $this->owner, $this->repository, $label);
                }
                if (!isset($composer['support']['issues']) && $this->hasIssues) {
                    $composer['support']['issues'] = sprintf('https://%s/%s/%s/issues', $this->originUrl, $this->owner, $this->repository);
                }
            }

            if (preg_match('{[a-f0-9]{40}}i', $identifier)) {
                $this->cache->write($this->repoConfig['asset-type'] . '-' . $identifier, json_encode($composer));
            }

            $this->infoCache[$identifier] = $composer;
        }

        return $this->infoCache[$identifier];
    }

    /**
     * Setup git driver.
     *
     * @param string $url
     */
    protected function setupGitDriver($url)
    {
        $this->gitDriver = new GitDriver(
            array(
                'url'        => $url,
                'asset-type' => $this->repoConfig['asset-type'],
                'filename'   => $this->repoConfig['filename']
            ),
            $this->io,
            $this->config,
            $this->process,
            $this->remoteFilesystem
        );
        $this->gitDriver->initialize();
    }
}
