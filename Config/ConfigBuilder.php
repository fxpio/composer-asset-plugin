<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Config;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Package\RootPackageInterface;

/**
 * Plugin Config builder.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class ConfigBuilder
{
    /**
     * List of the deprecated options.
     *
     * @var array
     */
    private static $deprecatedOptions = array(
        'installer-paths' => 'asset-installer-paths',
        'ignore-files' => 'asset-ignore-files',
        'private-bower-registries' => 'asset-private-bower-registries',
        'pattern-skip-version' => 'asset-pattern-skip-version',
        'optimize-with-installed-packages' => 'asset-optimize-with-installed-packages',
        'optimize-with-conjunctive' => 'asset-optimize-with-conjunctive',
        'repositories' => 'asset-repositories',
        'registry-options' => 'asset-registry-options',
        'vcs-driver-options' => 'asset-vcs-driver-options',
        'main-files' => 'asset-main-files',
    );

    /**
     * Validate the config of root package.
     *
     * @param IOInterface          $io          The composer input/output
     * @param RootPackageInterface $package     The root package
     * @param string               $commandName The command name
     */
    public static function validate(IOInterface $io, RootPackageInterface $package, $commandName = null)
    {
        if (null === $commandName || in_array($commandName, array('install', 'update', 'validate', 'require', 'remove'))) {
            $extra = (array) $package->getExtra();

            foreach (self::$deprecatedOptions as $new => $old) {
                if (array_key_exists($old, $extra)) {
                    $io->write(sprintf('<warning>The "extra.%s" option is deprecated, use the "config.fxp-asset.%s" option</warning>', $old, $new));
                }
            }
        }
    }

    /**
     * Build the config of plugin.
     *
     * @param Composer         $composer The composer
     * @param IOInterface|null $io       The composer input/output
     *
     * @return Config
     */
    public static function build(Composer $composer, $io = null)
    {
        $config = self::getConfigBase($composer, $io);
        $config = self::injectDeprecatedConfig($config, (array) $composer->getPackage()->getExtra());

        return new Config($config);
    }

    /**
     * Inject the deprecated keys in config if the config keys are not present.
     *
     * @param array $config The config
     * @param array $extra  The root package extra section
     *
     * @return array
     */
    private static function injectDeprecatedConfig(array $config, array $extra)
    {
        foreach (self::$deprecatedOptions as $key => $deprecatedKey) {
            if (array_key_exists($deprecatedKey, $extra) && !array_key_exists($key, $config)) {
                $config[$key] = $extra[$deprecatedKey];
            }
        }

        return $config;
    }

    /**
     * Get the base of data.
     *
     * @param Composer         $composer The composer
     * @param IOInterface|null $io       The composer input/output
     *
     * @return array
     */
    private static function getConfigBase(Composer $composer, $io = null)
    {
        $globalPackageConfig = self::getGlobalConfig($composer, 'composer', $io);
        $globalConfig = self::getGlobalConfig($composer, 'config', $io);
        $packageConfig = $composer->getPackage()->getConfig();
        $packageConfig = isset($packageConfig['fxp-asset']) && is_array($packageConfig['fxp-asset'])
            ? $packageConfig['fxp-asset']
            : array();

        return array_merge($globalPackageConfig, $globalConfig, $packageConfig);
    }

    /**
     * Get the data of the global config.
     *
     * @param Composer         $composer The composer
     * @param string           $filename The filename
     * @param IOInterface|null $io       The composer input/output
     *
     * @return array
     */
    private static function getGlobalConfig(Composer $composer, $filename, $io = null)
    {
        $home = self::getComposerHome($composer);
        $file = new JsonFile($home.'/'.$filename.'.json');
        $config = array();

        if ($file->exists()) {
            $data = $file->read();

            if (isset($data['config']['fxp-asset']) && is_array($data['config']['fxp-asset'])) {
                $config = $data['config']['fxp-asset'];

                if ($io instanceof IOInterface && $io->isDebug()) {
                    $io->writeError('Loading fxp-asset config in file '.$file->getPath());
                }
            }
        }

        return $config;
    }

    /**
     * Get the home directory of composer.
     *
     * @param Composer $composer The composer
     *
     * @return string
     */
    private static function getComposerHome(Composer $composer)
    {
        return null !== $composer->getConfig() && $composer->getConfig()->has('home')
            ? $composer->getConfig()->get('home')
            : '';
    }
}
