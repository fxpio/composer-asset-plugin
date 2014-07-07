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
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Repository\RepositoryInterface;
use Composer\Repository\RepositoryManager;

/**
 * Composer plugin.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class FxpAssetPlugin implements PluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $extra = $composer->getPackage()->getExtra();
        $rm = $composer->getRepositoryManager();

        $this->addRegistryRepositories($rm);
        $this->setVcsTypeRepositories($rm);

        if (isset($extra['asset-repositories']) && is_array($extra['asset-repositories'])) {
            $this->addRepositories($rm, $extra['asset-repositories']);
        }
    }

    /**
     * Adds asset registry repositories.
     *
     * @param RepositoryManager $rm
     */
    protected function addRegistryRepositories(RepositoryManager $rm)
    {
        foreach (Assets::getRegistries() as $assetType => $registryClass) {
            $rm->setRepositoryClass($assetType, $registryClass);
            $rm->addRepository($rm->createRepository($assetType, array('repository-manager' => $rm)));
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
     *
     * @throws \UnexpectedValueException When config of repository is not an array
     * @throws \UnexpectedValueException When the config of repository has not a type defined
     * @throws \UnexpectedValueException When the config of repository has an invalid type
     */
    protected function addRepositories(RepositoryManager $rm, array $repositories)
    {
        /* @var RepositoryInterface[] $repos */
        $repos = array();

        foreach ($repositories as $index => $repo) {
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
