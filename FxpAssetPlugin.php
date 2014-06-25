<?php

/**
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin;

use Composer\Composer;
use Composer\Config;
use Composer\DependencyResolver\DefaultPolicy;
use Composer\DependencyResolver\Pool;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Package\Link;
use Composer\Package\LinkConstraint\MultiConstraint;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Package\Version\VersionParser;
use Composer\Plugin\PluginInterface;
use Composer\Repository\InstalledFilesystemRepository;
use Composer\Repository\RepositoryInterface;

/**
 * Composer plugin.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class FxpAssetPlugin implements PluginInterface
{
    /**
     * @var bool
     */
    protected static $alreadyActivated = false;

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        if (!static::$alreadyActivated) {
            $this->addAssetRepositories($composer);
            $this->addRequireAssets($composer);
            static::$alreadyActivated = true;
        }
    }

    /**
     * Adds NPM and Bower repositories and Asset VCS Repository type.
     *
     * @param Composer $composer
     *
     * @throws \UnexpectedValueException
     */
    protected function addAssetRepositories(Composer $composer)
    {
        /* @var RepositoryInterface[] $repos */
        $repos = array();
        $extra = $composer->getPackage()->getExtra();
        $rm = $composer->getRepositoryManager();

        $rm->setRepositoryClass('npm', 'Fxp\Composer\AssetPlugin\Repository\NpmRepository');
        $rm->addRepository($rm->createRepository('npm', array('repositoryManager' => $rm)));

        $rm->setRepositoryClass('bower', 'Fxp\Composer\AssetPlugin\Repository\BowerRepository');
        $rm->addRepository($rm->createRepository('bower', array('repositoryManager' => $rm)));

        foreach (Assets::getTypes() as $assetType) {
            $rm->setRepositoryClass($assetType . '-vcs', 'Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository');
            $rm->setRepositoryClass($assetType . '-git', 'Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository');
        }

        // only for root package
        if ($composer->getPackage() instanceof RootPackageInterface && isset($extra['asset-repositories'])) {
            foreach ($extra['asset-repositories'] as $index => $repo) {
                if (!is_array($repo)) {
                    throw new \UnexpectedValueException('Repository '.$index.' ('.json_encode($repo).') should be an array, '.gettype($repo).' given');
                }
                if (!isset($repo['type'])) {
                    throw new \UnexpectedValueException('Repository '.$index.' ('.json_encode($repo).') must have a type defined');
                }
                $name = is_int($index) && isset($repo['url']) ? preg_replace('{^https?://}i', '', $repo['url']) : $index;
                while (isset($repos[$name])) {
                    $name .= '2';
                }
                if (false === strpos($repo['type'], '-')) {
                    throw new \UnexpectedValueException('Repository '.$index.' ('.json_encode($repo).') must have a type defined in this way: "%asset-type%-%type%"');
                }
                $repos[$name] = $rm->createRepository($repo['type'], $repo);

                $rm->addRepository($repos[$name]);
            }
        }
    }

    /**
     * Adds require assets.
     *
     * @param Composer $composer
     */
    protected function addRequireAssets(Composer $composer)
    {
        $package = $composer->getPackage();
        $config = $composer->getConfig();
        $globalRepository = $this->createGlobalRepository($config, $config->get('vendor-dir'));
        $pool = new Pool($package->getMinimumStability());
        $localRepo = $composer->getRepositoryManager()->getLocalRepository();
        $pool->addRepository($localRepo);

        if ($globalRepository) {
            $pool->addRepository($globalRepository);
        }

        foreach ($composer->getRepositoryManager()->getRepositories() as $repository) {
            $pool->addRepository($repository);
        }

        $autoloadPackages = array($package->getName() => $package);
        $autoloadPackages = $this->collectDependencies($pool, $autoloadPackages, $package, $package->isDev());
        $rootRequires = $package->getRequires();
        $rootDevRequires = $package->getDevRequires();

        /* @var PackageInterface $autoloadPackage */
        foreach ($autoloadPackages as $autoloadPackage) {
            $rootRequires = $this->getAssetRequires('require', $autoloadPackage, $rootRequires);

            if ($package->isDev()) {
                $rootDevRequires = $this->getAssetRequires('require-dev', $autoloadPackage, $rootDevRequires);
            }
        }

        $package->setRequires($rootRequires);
        $package->setDevRequires($rootDevRequires);
    }

    /**
     * Gets asset requires.
     *
     * @param string           $type
     * @param PackageInterface $package
     * @param array            $requires
     *
     * @return array The complete requires
     */
    protected function getAssetRequires($type, PackageInterface $package, array $requires)
    {
        $policy = new DefaultPolicy();
        $parser = new VersionParser();
        $extra = $package->getExtra();
        $section = 'asset-' . $type;

        if (isset($extra[$section])) {
            $assetRequires = $parser->parseLinks($package->getName(), $package->getPrettyVersion(), 'asset-require', $extra[$section]);

            /* @var Link $version */
            foreach ($assetRequires as $name => $version) {
                if (isset($requires[$name])) {
                    /* @var Link $previous */
                    $previous = $requires[$name];
                    $constraint = new MultiConstraint(array($previous->getConstraint(), $version->getConstraint()), false);
                    $version = new Link($version->getSource(), $version->getTarget(), $constraint, 'asset-require');
                }

                $requires[$name] = $version;
            }
        }

        return $requires;
    }

    /**
     * Recursively generates a map of package names to packages for all deps.
     *
     * @param Pool             $pool      Package pool of installed packages
     * @param array            $collected Current state of the map for recursion
     * @param PackageInterface $package   The package to analyze
     * @param bool             $isDev
     *
     * @return array Map of package names to packages
     */
    protected function collectDependencies(Pool $pool, array $collected, PackageInterface $package, $isDev = false)
    {
        $requires = $package->getRequires();

        if ($isDev) {
            $requires = array_merge(
                $requires,
                $package->getDevRequires()
            );
        }

        foreach ($requires as $requireLink) {
            $requiredPackage = $this->lookupInstalledPackage($pool, $requireLink);
            if ($requiredPackage && !isset($collected[$requiredPackage->getName()])) {
                $collected[$requiredPackage->getName()] = $requiredPackage;
                $collected = $this->collectDependencies($pool, $collected, $requiredPackage, $isDev);
            }
        }

        return $collected;
    }

    /**
     * Resolves a package link to a package in the installed pool.
     *
     * Since dependencies are already installed this should always find one.
     *
     * @param Pool $pool Pool of installed packages only
     * @param Link $link Package link to look up
     *
     * @return PackageInterface|null The found package
     */
    protected function lookupInstalledPackage(Pool $pool, Link $link)
    {
        $packages = $pool->whatProvides($link->getTarget(), $link->getConstraint());

        return (!empty($packages)) ? $packages[0] : null;
    }

    /**
     * Creates global repository.
     *
     * @param Config $config
     * @param string $vendorDir
     */
    protected function createGlobalRepository(Config $config, $vendorDir)
    {
        if ($config->get('home') == $vendorDir) {
            return null;
        }

        $path = $config->get('home').'/vendor/composer/installed.json';
        if (!file_exists($path)) {
            return null;
        }

        return new InstalledFilesystemRepository(new JsonFile($path));
    }
}
