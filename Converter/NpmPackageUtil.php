<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Converter;

/**
 * Utils for NPM package converter.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class NpmPackageUtil
{
    /**
     * Convert the npm package name.
     *
     * @param string $name The npm package name
     *
     * @return string
     */
    public static function convertName($name)
    {
        if (0 === strpos($name, '@') && false !== strpos($name, '/')) {
            $name = ltrim(str_replace('/', '--', $name), '@');
        }

        return $name;
    }

    /**
     * Revert the npm package name from composer package name.
     *
     * @param string $name The npm package name
     *
     * @return string
     */
    public static function revertName($name)
    {
        if (false !== strpos($name, '--')) {
            $name = '@'.str_replace('--', '/', $name);
        }

        return $name;
    }

    /**
     * Convert the author section.
     *
     * @param string|null $value The current value
     *
     * @return array
     */
    public static function convertAuthor($value)
    {
        if (null !== $value) {
            $value = array($value);
        }

        return $value;
    }

    /**
     * Convert the contributors section.
     *
     * @param string|null $value     The current value
     * @param string|null $prevValue The previous value
     *
     * @return array
     */
    public static function convertContributors($value, $prevValue)
    {
        $mergeValue = is_array($prevValue) ? $prevValue : array();
        $mergeValue = array_merge($mergeValue, is_array($value) ? $value : array());

        if (count($mergeValue) > 0) {
            $value = $mergeValue;
        }

        return $value;
    }

    /**
     * Convert the dist section.
     *
     * @param string|null $value The current value
     *
     * @return array
     */
    public static function convertDist($value)
    {
        if (is_array($value)) {
            $data = (array) $value;
            $value = array();

            foreach ($data as $type => $url) {
                if (is_string($url)) {
                    self::convertDistEntry($value, $type, $url);
                }
            }
        }

        return $value;
    }

    /**
     * Convert the each entry of dist section.
     *
     * @param array  $value The result
     * @param string $type  The dist type
     * @param string $url   The dist url
     */
    private static function convertDistEntry(array &$value, $type, $url)
    {
        $httpPrefix = 'http://';

        if (0 === strpos($url, $httpPrefix)) {
            $url = 'https://'.substr($url, strlen($httpPrefix));
        }

        if ('shasum' === $type) {
            $value[$type] = $url;
        } else {
            $value['type'] = 'tarball' === $type ? 'tar' : $type;
            $value['url'] = $url;
        }
    }
}
