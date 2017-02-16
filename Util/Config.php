<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Util;

use Composer\IO\IOInterface;
use Composer\Package\RootPackageInterface;

/**
 * Helper of package config.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class Config
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
     * Get the array config value.
     *
     * @param RootPackageInterface $package The root package
     * @param string               $key     The config key
     * @param array                $default The default value
     *
     * @return array
     */
    public static function getArray(RootPackageInterface $package, $key, array $default = array())
    {
        return (array) static::get($package, $key, $default);
    }

    /**
     * Get the config value.
     *
     * @param RootPackageInterface $package The root package
     * @param string               $key     The config key
     * @param mixed|null           $default The default value
     *
     * @return mixed|null
     */
    public static function get(RootPackageInterface $package, $key, $default = null)
    {
        $config = self::injectDeprecatedConfig(self::getConfigBase($package), (array) $package->getExtra(), $key);

        return array_key_exists($key, $config)
            ? $config[$key]
            : $default;
    }

    /**
     * Inject the deprecated keys in config if the config keys are not present.
     *
     * @param array  $config The
     * @param array  $extra
     * @param string $key
     *
     * @return array
     */
    private static function injectDeprecatedConfig(array $config, array $extra, $key)
    {
        $deprecatedKey = isset(self::$deprecatedOptions[$key])
            ? self::$deprecatedOptions[$key]
            : 'asset-'.$key;

        if (array_key_exists($deprecatedKey, $extra) && !array_key_exists($key, $config)) {
            $config[$key] = $extra[$deprecatedKey];
        }

        return $config;
    }

    /**
     * Get the base of data.
     *
     * @param RootPackageInterface $package The config data
     *
     * @return array
     */
    private static function getConfigBase($package)
    {
        $config = $package->getConfig();

        return isset($config['fxp-asset']) && is_array($config['fxp-asset'])
            ? $config['fxp-asset']
            : array();
    }
}
