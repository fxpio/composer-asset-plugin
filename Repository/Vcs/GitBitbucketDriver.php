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
use Composer\Repository\Vcs\GitBitbucketDriver as BaseGitBitbucketDriver;

/**
 * Git Bitbucket vcs driver.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class GitBitbucketDriver extends BaseGitBitbucketDriver
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

        $this->cache = new Cache($this->io, $this->config->get('cache-repo-dir').'/'.$this->originUrl.'/'.$this->owner.'/'.$this->repository);
    }

    /**
     * {@inheritDoc}
     */
    public function getComposerInformation($identifier)
    {
        $this->infoCache[$identifier] = Util::readCache($this->infoCache, $this->cache, $this->repoConfig['asset-type'], $identifier);

        if (!isset($this->infoCache[$identifier])) {
            $resource = $this->getScheme() . '://bitbucket.org/'.$this->owner.'/'.$this->repository.'/raw/'.$identifier.'/'.$this->repoConfig['filename'];
            $composer = $this->getComposerContent($resource, $identifier);

            Util::writeCache($this->cache, $this->repoConfig['asset-type'], $identifier, $composer);
            $this->infoCache[$identifier] = $composer;
        }

        return $this->infoCache[$identifier];
    }

    /**
     * Gets content of composer information.
     *
     * @param string $resource
     * @param string $identifier
     *
     * @return array
     */
    protected function getComposerContent($resource, $identifier)
    {
        try {
            $composer = $this->getContents($resource);
        } catch (\Exception $e) {
            $composer = false;
        }

        if ($composer) {
            $composer = (array) JsonFile::parseJson((string) $composer, $resource);
            $composer = $this->formatComposerContent($composer, $identifier);

            return $composer;
        }

        return array('_nonexistent_package' => true);
    }

    /**
     * Format composer content.
     *
     * @param array  $composer
     * @param string $identifier
     *
     * @return array
     */
    protected function formatComposerContent(array $composer, $identifier)
    {
        $resource = $this->getScheme() . '://api.bitbucket.org/1.0/repositories/'.$this->owner.'/'.$this->repository.'/changesets/'.$identifier;
        $composer = Util::addComposerTime($composer, 'timestamp', $resource, $this);

        return $composer;
    }
}
