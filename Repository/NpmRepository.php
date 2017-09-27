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

use Composer\DependencyResolver\Pool;
use Composer\IO\IOInterface;
use Composer\Package\CompletePackageInterface;
use Composer\Package\Loader\ArrayLoader;
use Composer\Repository\ArrayRepository;
use Fxp\Composer\AssetPlugin\Converter\NpmPackageUtil;
use Fxp\Composer\AssetPlugin\Converter\PackageUtil;
use Fxp\Composer\AssetPlugin\Exception\InvalidCreateRepositoryException;

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
        return $this->canonicalizeUrl($this->baseUrl.'/%package%');
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchUrl()
    {
        return $this->canonicalizeUrl($this->baseUrl.'/-/all');
    }

    /**
     * {@inheritdoc}
     */
    public function search($query, $mode = 0, $type = null)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    protected function buildPackageUrl($packageName)
    {
        $packageName = urlencode(NpmPackageUtil::revertName($packageName));
        $packageName = str_replace('%40', '@', $packageName);

        return parent::buildPackageUrl($packageName);
    }

    /**
     * {@inheritdoc}
     */
    protected function createVcsRepositoryConfig(array $data, $registryName = null)
    {
        $type = isset($data['repository']['type']) ? $data['repository']['type'] : 'vcs';

        // Add release date in $packageConfigs
        if (isset($data['versions']) && isset($data['time'])) {
            $time = $data['time'];
            array_walk($data['versions'], function (&$packageConfigs, $version) use ($time) {
                PackageUtil::convertStringKey($time, $version, $packageConfigs, 'time');
            });
        }

        return array(
            'type' => $this->assetType->getName().'-'.$type,
            'url' => $this->getVcsRepositoryUrl($data, $registryName),
            'name' => $registryName,
            'registry-versions' => isset($data['versions']) ? $this->createArrayRepositoryConfig($data['versions']) : array(),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function whatProvidesManageException(Pool $pool, $name, \Exception $exception)
    {
        if ($exception instanceof InvalidCreateRepositoryException) {
            $data = $exception->getData();

            if (isset($data['versions']) && !empty($data['versions'])) {
                $this->putArrayRepositoryConfig($data['versions'], $name, $pool);

                return;
            }
        }

        parent::whatProvidesManageException($pool, $name, $exception);
    }

    /**
     * Create and put the array repository with the asset configs.
     *
     * @param array  $packageConfigs The configs of assets package versions
     * @param string $name           The asset package name
     * @param Pool   $pool           The pool
     */
    protected function putArrayRepositoryConfig(array $packageConfigs, $name, Pool $pool)
    {
        $packages = $this->createArrayRepositoryConfig($packageConfigs);
        $repo = new ArrayRepository($packages);
        Util::addRepositoryInstance($this->io, $this->repositoryManager, $this->repos, $name, $repo, $pool);

        $this->providers[$name] = array();
    }

    /**
     * Create the array repository with the asset configs.
     *
     * A warning message is displayed if the constraint versions of packages
     * are broken. These versions are skipped and the plugin hope that other
     * versions will be OK.
     *
     * @param array $packageConfigs The configs of assets package versions
     *
     * @return CompletePackageInterface[]
     */
    protected function createArrayRepositoryConfig(array $packageConfigs)
    {
        $packages = array();
        $loader = new ArrayLoader();

        foreach ($packageConfigs as $version => $config) {
            try {
                $config['version'] = $version;
                $config = $this->assetType->getPackageConverter()->convert($config);
                $config = $this->assetRepositoryManager->solveResolutions($config);
                $packages[] = $loader->load($config);
            } catch (\UnexpectedValueException $exception) {
                $this->io->write("<warning>Skipped {$config['name']} version {$version}: {$exception->getMessage()}</warning>", IOInterface::VERBOSE);
            }
        }

        return $packages;
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
            $msg .= PHP_EOL.'If the config comes from the NPM Registry, override the config with a custom Asset VCS Repository';
            $ex = new InvalidCreateRepositoryException($msg);
            $ex->setData($data);

            throw $ex;
        }

        return $this->convertUrl((string) $data['repository']['url']);
    }

    /**
     * Convert the url repository.
     *
     * @param string $url The url
     *
     * @return string The url converted
     */
    private function convertUrl($url)
    {
        if (0 === strpos($url, 'git+http')) {
            return substr($url, 4);
        }

        return $url;
    }
}
