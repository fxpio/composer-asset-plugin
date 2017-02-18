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

/**
 * Helper of package config.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
final class Config
{
    /**
     * @var array
     */
    private $config;

    /**
     * Constructor.
     *
     * @param array $config The config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get the array config value.
     *
     * @param string $key     The config key
     * @param array  $default The default value
     *
     * @return array
     */
    public function getArray($key, array $default = array())
    {
        return $this->get($key, $default);
    }

    /**
     * Get the config value.
     *
     * @param string     $key     The config key
     * @param mixed|null $default The default value
     *
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->config)
            ? $this->config[$key]
            : $default;
    }
}
