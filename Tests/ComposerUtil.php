<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests;

use Composer\Composer;
use Composer\Package\Version\VersionParser;

/**
 * Helper for Composer.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class ComposerUtil
{
    /**
     * @param string[] $value The map of composer versions and the values
     *
     * @return string
     */
    public static function getValueByVersion(array $value)
    {
        $versionParser = new VersionParser();
        $composerVersionConstraint = $versionParser->parseConstraints(Composer::VERSION);

        foreach ($value as $versionRange => $content) {
            $rangeConstraint = $versionParser->parseConstraints($versionRange);

            if ($composerVersionConstraint->matches($rangeConstraint)) {
                return $content;
            }
        }

        throw new \InvalidArgumentException('The composer version is not found');
    }
}
