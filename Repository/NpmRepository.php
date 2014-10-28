<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Repository;

use Composer\Repository\InvalidRepositoryException;

/**
 * NPM repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class NpmRepository extends AbstractAssetsRepository
{
    /**
     * {@inheritdoc}
     */
    protected function getType()
    {
        return 'npm';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUrl()
    {
        return 'https://registry.npmjs.org';
    }

    /**
     * {@inheritdoc}
     */
    protected function getPackageUrl()
    {
        return $this->canonicalizeUrl($this->baseUrl . '/%package%');
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchUrl()
    {
        return $this->canonicalizeUrl($this->baseUrl . '/-/all');
    }

    /**
     * {@inheritDoc}
     */
    public function search($query, $mode = 0)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    protected function createVcsRepositoryConfig(array $data, $registryName = null)
    {
        $type = isset($data['repository']['type']) ? $data['repository']['type'] : 'vcs';

        return array(
            'type' => $this->assetType->getName() . '-' . $type,
            'url'  => $this->getVcsRepositoryUrl($data, $registryName),
            'name' => $registryName,
        );
    }

    /**
     * Get the URL of VCS repository.
     *
     * @param array  $data         The repository config
     * @param string $registryName The package name in asset registry
     *
     * @return string
     *
     * @throws InvalidRepositoryException When the repository.url parameter does not exist
     */
    protected function getVcsRepositoryUrl(array $data, $registryName = null)
    {
        if (!isset($data['repository']['url'])) {
            $msg = sprintf('The "repository.url" parameter of "%s" %s asset package must be present for create a VCS Repository', $registryName, $this->assetType->getName());
            $msg .= PHP_EOL . 'If the config comes from the NPM Registry, override the config with a custom Asset VCS Repository';

            throw new InvalidRepositoryException($msg);
        }

        return (string) $data['repository']['url'];
    }
}
