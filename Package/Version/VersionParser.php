<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Package\Version;

use Composer\Package\Version\VersionParser as BaseVersionParser;
use UnexpectedValueException;

/**
 * Lazy loader for asset package.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class VersionParser extends BaseVersionParser
{
    /**
     * Returns the stability of a version.
     *
     * @param string $version
     *
     * @return string
     */
    public static function parseStability($version)
    {
        $stability = parent::parseStability($version);

        return false !== strpos($version, '-patch') ? 'dev' : $stability;
    }
    
    /**
     * Normalizes a version string to be able to perform comparisons on it.
     *
     * @param string $version
     * @param string $fullVersion optional complete version string to give more context
     *
     * @throws \UnexpectedValueException
     *
     * @return string
     */
    public function normalize($version, $fullVersion = null)
    {
        try{
            $version = parent::normalize($version);
        }
        catch(UnexpectedValueException $exception){
			$version = 'dev';
        }
        return $version;
    }
}
