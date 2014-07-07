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
        /* @var RepositoryInterface[] $repos */
        $repos = array();
        $extra = $composer->getPackage()->getExtra();
        $rm = $composer->getRepositoryManager();

        $rm->setRepositoryClass('npm', 'Fxp\Composer\AssetPlugin\Repository\NpmRepository');
        $rm->addRepository($rm->createRepository('npm', array('repository-manager' => $rm)));

        $rm->setRepositoryClass('bower', 'Fxp\Composer\AssetPlugin\Repository\BowerRepository');
        $rm->addRepository($rm->createRepository('bower', array('repository-manager' => $rm)));

        foreach (Assets::getTypes() as $assetType) {
            $rm->setRepositoryClass($assetType . '-vcs', 'Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository');
            $rm->setRepositoryClass($assetType . '-github', 'Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository');
            $rm->setRepositoryClass($assetType . '-git', 'Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository');
        }

        if (isset($extra['asset-repositories'])) {
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
}
