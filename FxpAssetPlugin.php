<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin;

use Composer\Composer;
use Composer\DependencyResolver\Pool;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\InstallerEvent;
use Composer\Installer\InstallerEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Repository\RepositoryInterface;
use Composer\Repository\RepositoryManager;
use Fxp\Composer\AssetPlugin\Event\VcsRepositoryEvent;
use Fxp\Composer\AssetPlugin\Installer\AssetInstaller;
use Fxp\Composer\AssetPlugin\Installer\BowerInstaller;
use Fxp\Composer\AssetPlugin\Repository\VcsPackageFilter;
use Fxp\Composer\AssetPlugin\Repository\Util;

/**
 * Composer plugin.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class FxpAssetPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var RepositoryInterface[]
     */
    protected $repos = array();

    /**
     * @var Pool
     */
    protected $pool;

    /**
     * @var VcsPackageFilter
     */
    protected $packageFilter;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            AssetEvents::ADD_VCS_REPOSITORIES => array(
                array('onAddVcsRepositories', 0),
            ),
            InstallerEvents::PRE_DEPENDENCIES_SOLVING => array(
                array('onPreDependenciesSolving', 0),
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->packageFilter = new VcsPackageFilter($composer->getPackage());
        $extra = $composer->getPackage()->getExtra();
        $rm = $composer->getRepositoryManager();

        $this->addRegistryRepositories($rm, $extra);
        $this->setVcsTypeRepositories($rm);

        if (isset($extra['asset-repositories']) && is_array($extra['asset-repositories'])) {
            $this->addRepositories($rm, $extra['asset-repositories']);
        }

        $this->addInstallers($composer, $io);
    }

    /**
     * Adds vcs repositories in manager from asset dependencies with url version.
     *
     * @param VcsRepositoryEvent $event
     */
    public function onAddVcsRepositories(VcsRepositoryEvent $event)
    {
        if (null !== $this->composer) {
            $rm = $this->composer->getRepositoryManager();
            $this->addRepositories($rm, $event->getRepositories(), $this->pool);
        }
    }

    /**
     * Add pool in plugin.
     *
     * @param InstallerEvent $event
     */
    public function onPreDependenciesSolving(InstallerEvent $event)
    {
        $this->pool = $event->getPool();
    }

    /**
     * Adds asset registry repositories.
     *
     * @param RepositoryManager $rm
     * @param array             $extra
     */
    protected function addRegistryRepositories(RepositoryManager $rm, array $extra)
    {
        $opts = array_key_exists('asset-registry-options', $extra)
            ? $extra['asset-registry-options']
            : array();

        foreach (Assets::getRegistries() as $assetType => $registryClass) {
            $config = array(
                'repository-manager' => $rm,
                'vcs-package-filter' => $this->packageFilter,
                'asset-options'      => $this->crateAssetOptions($opts, $assetType),
            );

            $rm->setRepositoryClass($assetType, $registryClass);
            $rm->addRepository($rm->createRepository($assetType, $config));
        }
    }

    /**
     * Sets vcs type repositories.
     *
     * @param RepositoryManager $rm
     */
    protected function setVcsTypeRepositories(RepositoryManager $rm)
    {
        foreach (Assets::getTypes() as $assetType) {
            foreach (Assets::getVcsRepositoryDrivers() as $driverType => $repositoryClass) {
                $rm->setRepositoryClass($assetType . '-' . $driverType, $repositoryClass);
            }
        }
    }

    /**
     * Adds asset vcs repositories.
     *
     * @param RepositoryManager $rm
     * @param array             $repositories
     * @param Pool|null         $pool
     *
     * @throws \UnexpectedValueException When config of repository is not an array
     * @throws \UnexpectedValueException When the config of repository has not a type defined
     * @throws \UnexpectedValueException When the config of repository has an invalid type
     */
    protected function addRepositories(RepositoryManager $rm, array $repositories, Pool $pool = null)
    {
        foreach ($repositories as $index => $repo) {
            $this->validateRepositories($index, $repo);

            if ('package' === $repo['type']) {
                $name = $repo['package']['name'];

            } else {
                $name = is_int($index) ? preg_replace('{^https?://}i', '', $repo['url']) : $index;
                $name = isset($repo['name']) ? $repo['name'] : $name;
                $repo['vcs-package-filter'] = $this->packageFilter;
            }

            Util::addRepository($rm, $this->repos, $name, $repo, $pool);
        }
    }

    /**
     * Validates the config of repositories.
     *
     * @param int|string  $index The index
     * @param mixed|array $repo  The config repo
     *
     * @throws \UnexpectedValueException
     */
    protected function validateRepositories($index, $repo)
    {
        if (!is_array($repo)) {
            throw new \UnexpectedValueException('Repository '.$index.' ('.json_encode($repo).') should be an array, '.gettype($repo).' given');
        }
        if (!isset($repo['type'])) {
            throw new \UnexpectedValueException('Repository '.$index.' ('.json_encode($repo).') must have a type defined');
        }

        $this->validatePackageRepositories($index, $repo);
        $this->validateVcsRepositories($index, $repo);
    }

    /**
     * Validates the config of package repositories.
     *
     * @param int|string  $index The index
     * @param mixed|array $repo  The config repo
     *
     * @throws \UnexpectedValueException
     */
    protected function validatePackageRepositories($index, $repo)
    {
        if ('package' !== $repo['type']) {
            return;
        }

        if (!isset($repo['package'])) {
            throw new \UnexpectedValueException('Repository '.$index.' ('.json_encode($repo).') must have a package definition"');
        }

        foreach (array('name', 'type', 'version', 'dist') as $key) {
            if (!isset($repo['package'][$key])) {
                throw new \UnexpectedValueException('Repository '.$index.' ('.json_encode($repo).') must have the "'.$key.'" key  in the package definition"');
            }
        }
    }

    /**
     * Validates the config of vcs repositories.
     *
     * @param int|string  $index The index
     * @param mixed|array $repo  The config repo
     *
     * @throws \UnexpectedValueException
     */
    protected function validateVcsRepositories($index, $repo)
    {
        if ('package' === $repo['type']) {
            return;
        }

        if (false === strpos($repo['type'], '-')) {
            throw new \UnexpectedValueException('Repository '.$index.' ('.json_encode($repo).') must have a type defined in this way: "%asset-type%-%type%"');
        }
        if (!isset($repo['url'])) {
            throw new \UnexpectedValueException('Repository '.$index.' ('.json_encode($repo).') must have a url defined');
        }
    }

    /**
     * Creates the asset options.
     *
     * @param array  $extra     The composer extra section of asset options
     * @param string $assetType The asset type
     *
     * @return array The asset registry options
     */
    protected function crateAssetOptions(array $extra, $assetType)
    {
        $options = array();

        foreach ($extra as $key => $value) {
            if (0 === strpos($key, $assetType . '-')) {
                $key = substr($key, strlen($assetType) + 1);
                $options[$key] = $value;
            }
        }

        return $options;
    }

    /**
     * Adds asset installers.
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    protected function addInstallers(Composer $composer, IOInterface $io)
    {
        $im = $composer->getInstallationManager();

        $im->addInstaller(new BowerInstaller($io, $composer, Assets::createType('bower')));
        $im->addInstaller(new AssetInstaller($io, $composer, Assets::createType('npm')));
    }
}
