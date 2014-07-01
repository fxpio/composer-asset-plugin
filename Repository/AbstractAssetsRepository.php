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
use Composer\DependencyResolver\Pool;
use Composer\Downloader\TransportException;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Repository\ComposerRepository;
use Composer\Repository\RepositoryManager;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Abstract assets repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class AbstractAssetsRepository extends ComposerRepository
{
    /**
     * @var AssetTypeInterface
     */
    protected $assetType;

    /**
     * @var RepositoryManager
     */
    protected $rm;

    /**
     * @var AssetVcsRepository[]
     */
    protected $repos;

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
        $repoConfig = array_merge($repoConfig, array(
            'url' => $this->getUrl(),
        ));

        parent::__construct($repoConfig, $io, $config, $eventDispatcher);

        $this->lazyProvidersUrl = $this->canonicalizeUrl($this->baseUrl . '/' . $this->getSlugOfGetPackage());
        $this->providersUrl = $this->lazyProvidersUrl;
        $this->searchUrl = $this->canonicalizeUrl($this->baseUrl . '/' . $this->getSlugOfSearch());
        $this->hasProviders = true;
        $this->rm = $repoConfig['repositoryManager'];
        $this->repos = array();
    }

    /**
     * {@inheritDoc}
     */
    public function findPackage($name, $version)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function findPackages($name, $version = null)
    {
        return array();
    }

    /**
     * {@inheritDoc}
     */
    public function search($query, $mode = 0)
    {
        $url = str_replace('%query%', $query, $this->searchUrl);
        $hostname = parse_url($url, PHP_URL_HOST) ?: $url;
        $json = $this->rfs->getContents($hostname, $url, false);
        $data = JsonFile::parseJson($json, $url);
        $results = array();

        /* @var array $item */
        foreach ($data as $item) {
            $results[] = $this->createSearchItem($item);
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function whatProvides(Pool $pool, $name)
    {
        $assetPrefix = $this->getAssetType()->getComposerVendorName() . '/';

        if (false === strpos($name, $assetPrefix)) {
            return array();
        }

        if (isset($this->providers[$name])) {
            return $this->providers[$name];
        }

        if (!extension_loaded('openssl') && 'https' === substr($this->url, 0, 5)) {
            throw new \RuntimeException('You must enable the openssl extension in your php.ini to load information from '.$this->url);
        }

        try {
            $packageName = substr($name, strlen($assetPrefix));
            $packageUrl = str_replace('%package%', $packageName, $this->lazyProvidersUrl);

            $data = $this->fetchFile($packageUrl, $packageName . '-package.json');
            $repo = $this->createVcsRepositoryConfig($data);

            if (!isset($this->repos[$name])) {
                $repo = $this->rm->createRepository($repo['type'], $repo);
                $this->rm->addRepository($repo);
                $this->repos[$name] = $repo;
                $pool->addRepository($repo);
            }

            $this->providers[$name] = array();

        } catch (TransportException $ex) {
            $this->providers[$name] = array();
        }

        return $this->providers[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function getPackages()
    {
        throw new \LogicException('Asset repositories can not load the complete list of packages.');
    }

    /**
     * {@inheritDoc}
     */
    public function getProviderNames()
    {
        throw new \LogicException('Asset repositories can not get the provider names.');
    }

    /**
     * {@inheritDoc}
     */
    public function getMinimalPackages()
    {
        return array();
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize()
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function loadRootServerFile()
    {
        return array();
    }

    /**
     * Gets the asset type.
     *
     * @return AssetTypeInterface
     */
    protected function getAssetType()
    {
        return $this->assetType;
    }

    /**
     * Creates the search result item.
     *
     * @param array $item.
     *
     * @return array An array('name' => '...', 'description' => '...')
     */
    protected function createSearchItem(array $item)
    {
        return array(
            'name'        => $this->getAssetType()->getComposerVendorName() . '/' . $item['name'],
            'description' => null,
        );
    }

    /**
     * Gets the URL of repository.
     *
     * @return string
     */
    abstract protected function getUrl();

    /**
     * Gets the slug for get package URL.
     *
     * @return string
     */
    abstract protected function getSlugOfGetPackage();

    /**
     * Gets the slug for seach URL.
     *
     * @return string
     */
    abstract protected function getSlugOfSearch();

    /**
     * Creates a config of vcs repository.
     *
     * @param array $data
     *
     * @return array An array('type' => '...', 'url' => '...')
     */
    abstract protected function createVcsRepositoryConfig(array $data);
}
