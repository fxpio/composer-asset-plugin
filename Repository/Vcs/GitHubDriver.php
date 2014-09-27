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
     * @var string|null
     */
    protected $redirectApi;

    /**
     * {@inheritDoc}
     */
    public function getComposerInformation($identifier)
    {
        if ($this->gitDriver) {
            return $this->gitDriver->getComposerInformation($identifier);
        }

        $this->infoCache[$identifier] = Util::readCache($this->infoCache, $this->cache, $this->repoConfig['asset-type'], $identifier);

        if (!isset($this->infoCache[$identifier])) {
            $resource = $this->getApiUrl() . '/repos/'.$this->owner.'/'.$this->repository.'/contents/' . $this->repoConfig['filename'] . '?ref='.urlencode($identifier);
            $composer = $this->getComposerContent($resource);

            if ($composer) {
                $composer = $this->convertComposerContent($composer, $resource, $identifier);
            } else {
                $composer = array('_nonexistent_package' => true);
            }

            Util::writeCache($this->cache, $this->repoConfig['asset-type'], $identifier, $composer);
            $this->infoCache[$identifier] = $composer;
        }

        return $this->infoCache[$identifier];
    }

    /**
     * Gets content of composer information.
     *
     * @param string $resource
     *
     * @return bool|null|string
     *
     * @throws \RuntimeException
     * @throws \Composer\Downloader\TransportException
     * @throws \Exception
     */
    protected function getComposerContent($resource)
    {
        $notFoundRetries = 2;
        $composer = null;

        while ($notFoundRetries) {
            try {
                $composer = $this->parseComposerContent($resource);
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

        return $composer;
    }

    /**
     * Parse the composer content.
     *
     * @param string $resource
     *
     * @return array
     *
     * @throws \RuntimeException When the resource could not be retrieved
     */
    protected function parseComposerContent($resource)
    {
        $composer = (array) JsonFile::parseJson((string) $this->getContents($resource));
        if (empty($composer['content']) || $composer['encoding'] !== 'base64' || !($composer = base64_decode($composer['content']))) {
            throw new \RuntimeException('Could not retrieve ' . $this->repoConfig['filename'] . ' from '.$resource);
        }

        return $composer;
    }

    /**
     * Converts json composer file to array.
     *
     * @param string $composer
     * @param string $resource
     * @param string $identifier
     *
     * @return array
     */
    protected function convertComposerContent($composer, $resource, $identifier)
    {
        $composer = JsonFile::parseJson($composer, $resource);

        if (!isset($composer['time'])) {
            $resource = $this->getApiUrl() . '/repos/'.$this->owner.'/'.$this->repository.'/commits/'.urlencode($identifier);
            $commit = JsonFile::parseJson((string) $this->getContents($resource), $resource);
            $composer['time'] = $commit['commit']['committer']['date'];
        }
        if (!isset($composer['support']['source'])) {
            $label = array_search($identifier, $this->getTags()) ?: array_search($identifier, $this->getBranches()) ?: $identifier;
            $composer['support']['source'] = sprintf('https://%s/%s/%s/tree/%s', $this->originUrl, $this->owner, $this->repository, $label);
        }
        if (!isset($composer['support']['issues']) && $this->hasIssues) {
            $composer['support']['issues'] = sprintf('https://%s/%s/%s/issues', $this->originUrl, $this->owner, $this->repository);
        }

        return $composer;
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

    /**
     * {@inheritDoc}
     */
    protected function getContents($url, $fetchingRepoData = false)
    {
        $url = $this->getValidContentUrl($url);

        if (null !== $this->redirectApi) {
            return parent::getContents($url, $fetchingRepoData);
        }

        try {
            $contents = $this->getRemoteContents($url);
            $this->redirectApi = false;

            return $contents;

        } catch (TransportException $e) {
            if ($this->hasRedirectUrl($url)) {
                $url = $this->getValidContentUrl($url);
            }

            return parent::getContents($url, $fetchingRepoData);
        }
    }

    /**
     * @param string $url The url
     *
     * @return string The url redirected
     */
    protected function getValidContentUrl($url)
    {
        if (null === $this->redirectApi && false !== $redirectApi = $this->cache->read('redirect-api')) {
            $this->redirectApi = $redirectApi;
        }

        if (is_string($this->redirectApi) && 0 === strpos($url, $this->getRepositoryApiUrl())) {
            $url = $this->redirectApi . substr($url, strlen($this->getRepositoryApiUrl()));
        }

        return $url;
    }

    /**
     * Check if the driver must find the new url.
     *
     * @param string $url The url
     *
     * @return bool
     */
    protected function hasRedirectUrl($url)
    {
        if (null === $this->redirectApi && 0 === strpos($url, $this->getRepositoryApiUrl())) {
            $this->redirectApi = $this->getNewRepositoryUrl();

            if (is_string($this->redirectApi)) {
                $this->cache->write('redirect-api', $this->redirectApi);
            }
        }

        return is_string($this->redirectApi);
    }

    /**
     * Get the new url of repository.
     *
     * @return string|false The new url or false if there is not a new url
     */
    protected function getNewRepositoryUrl()
    {
        try {
            $this->getRemoteContents($this->getRepositoryUrl());
            $headers = $this->remoteFilesystem->getLastHeaders();

            if (!empty($headers[0]) && preg_match('{^HTTP/\S+ (30[1278])}i', $headers[0], $match)) {
                array_shift($headers);

                return $this->findNewLocationInHeader($headers);
            }

            return false;

        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * Find the new url api in the header.
     *
     * @param array $headers The http header
     *
     * @return string|false
     */
    protected function findNewLocationInHeader(array $headers)
    {
        $url = false;

        foreach ($headers as $header) {
            if (0 === strpos($header, 'Location:')) {
                $newUrl = trim(substr($header, 9));
                preg_match('#^(?:(?:https?|git)://([^/]+)/|git@([^:]+):)([^/]+)/(.+?)(?:\.git|/)?$#', $newUrl, $match);
                $owner = $match[3];
                $repository = $match[4];
                $paramPos = strpos($repository, '?');
                $repository = is_int($paramPos) ? substr($match[4], 0, $paramPos) : $repository;
                $url = $this->getRepositoryApiUrl($owner, $repository);
                break;
            }
        }

        return $url;
    }

    /**
     * Get the url API of the repository.
     *
     * @param string $owner
     * @param string $repository
     *
     * @return string
     */
    protected function getRepositoryApiUrl($owner = null, $repository = null)
    {
        $owner = null !== $owner ? $owner : $this->owner;
        $repository = null !== $repository ? $repository : $this->repository;

        return $this->getApiUrl() . '/repos/'.$owner.'/'.$repository;
    }

    /**
     * Get the remote content.
     *
     * @param string $url
     *
     * @return bool|string
     */
    protected function getRemoteContents($url)
    {
        return $this->remoteFilesystem->getContents($this->originUrl, $url, false);
    }
}
