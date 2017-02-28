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
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\InstallerEvent;
use Composer\Installer\InstallerEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Repository\InstalledFilesystemRepository;
use Fxp\Composer\AssetPlugin\Config\Config;
use Fxp\Composer\AssetPlugin\Config\ConfigBuilder;
use Fxp\Composer\AssetPlugin\Repository\AssetRepositoryManager;
use Fxp\Composer\AssetPlugin\Repository\ResolutionManager;
use Fxp\Composer\AssetPlugin\Repository\VcsPackageFilter;
use Fxp\Composer\AssetPlugin\Util\AssetPlugin;

/**
 * Composer plugin.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class FxpAssetPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var VcsPackageFilter
     */
    protected $packageFilter;

    /**
     * @var AssetRepositoryManager
     */
    protected $assetRepositoryManager;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            PluginEvents::COMMAND => array(
                array('onPluginCommand', 0),
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
        $this->config = ConfigBuilder::build($composer, $io);

        if ($this->config->get('enabled', true)) {
            /* @var InstalledFilesystemRepository $installedRepository */
            $installedRepository = $composer->getRepositoryManager()->getLocalRepository();
            $this->composer = $composer;
            $this->io = $io;
            $this->packageFilter = new VcsPackageFilter($this->config, $composer->getPackage(), $composer->getInstallationManager(), $installedRepository);
            $this->assetRepositoryManager = new AssetRepositoryManager($io, $composer->getRepositoryManager(), $this->config, $this->packageFilter);
            $this->assetRepositoryManager->setResolutionManager(new ResolutionManager($this->config->getArray('resolutions')));

            AssetPlugin::addRegistryRepositories($this->assetRepositoryManager, $this->packageFilter, $this->config);
            AssetPlugin::setVcsTypeRepositories($composer->getRepositoryManager());

            $this->assetRepositoryManager->addRepositories($this->config->getArray('repositories'));

            AssetPlugin::addInstallers($this->config, $composer, $io);
        }
    }

    /**
     * Disable the package filter for all command, but for install and update command.
     *
     * @param CommandEvent $event
     */
    public function onPluginCommand(CommandEvent $event)
    {
        if ($this->config->get('enabled', true)) {
            ConfigBuilder::validate($this->io, $this->composer->getPackage(), $event->getCommandName());

            if (!in_array($event->getCommandName(), array('install', 'update'))) {
                $this->packageFilter->setEnabled(false);
            }
        }
    }

    /**
     * Add pool in asset repository manager.
     *
     * @param InstallerEvent $event
     */
    public function onPreDependenciesSolving(InstallerEvent $event)
    {
        if ($this->config->get('enabled', true)) {
            $this->assetRepositoryManager->setPool($event->getPool());
        }
    }

    /**
     * Get the plugin config.
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }
}
