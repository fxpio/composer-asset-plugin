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

use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Fxp\Composer\AssetPlugin\Exception\InvalidCreateRepositoryException;

/**
 * Bower repository for Private Instaltions.
 *
 * @author Marcus Stueben <marcus@it-stueben.de>
 */
class BowerPrivateRepository extends AbstractAssetsRepository
{
    /**
     * Constructor.
     *
     * @param array           $repoConfig
     * @param IOInterface     $io
     * @param Config          $config
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(array $repoConfig, IOInterface $io, Config $config, EventDispatcher $eventDispatcher = null)
    {
        $this->url = isset($repoConfig['private-registry-url'])
            ? $repoConfig['private-registry-url']
            : null;

        parent::__construct($repoConfig, $io, $config, $eventDispatcher);
    }

    /**
     * {@inheritdoc}
     */
    protected function getType()
    {
        return 'bower';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUrl()
    {
        return $this->url;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPackageUrl()
    {
        return $this->canonicalizeUrl($this->baseUrl.'/%package%');
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchUrl()
    {
        return $this->canonicalizeUrl($this->baseUrl.'/search/%query%');
    }

    /**
     * {@inheritdoc}
     */
    protected function createVcsRepositoryConfig(array $data, $registryName = null)
    {
        $myArray = array();
        $myArray['repository'] = $data;

        return array(
            'type' => $this->assetType->getName().'-vcs',
            'url' => $this->getVcsRepositoryUrl($myArray, $registryName),
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
     * @throws InvalidCreateRepositoryException When the repository.url parameter does not exist
     */
    protected function getVcsRepositoryUrl(array $data, $registryName = null)
    {
        if (!isset($data['repository']['url'])) {
            $msg = sprintf('The "repository.url" parameter of "%s" %s asset package must be present for create a VCS Repository', $registryName, $this->assetType->getName());
            $msg .= PHP_EOL.'If the config comes from the Bower Private Registry, override the config with a custom Asset VCS Repository';
            $ex = new InvalidCreateRepositoryException($msg);
            $ex->setData($data);

            throw $ex;
        }

        return (string) $data['repository']['url'];
    }
}
