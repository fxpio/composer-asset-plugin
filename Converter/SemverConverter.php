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

use Composer\Package\Version\VersionParser;

/**
 * Converter for Semver syntax version to composer syntax version.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class SemverConverter implements VersionConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function convertVersion($version)
    {
        if ('latest' === $version) {
            return 'default';
        }

        $prefix = preg_match('/^[a-z]/', $version) ? substr($version, 0, 1) : '';
        $version = substr($version, strlen($prefix));
        $version = $this->convertVersionMetadata($version);

        return $prefix . $version;
    }

    /**
     * {@inheritdoc}
     */
    public function convertRange($range)
    {
        $range = $this->cleanRange($range);

        return $this->matchRange($range);
    }

    /**
     * Creates a pattern with the version prefix pattern.
     *
     * @param string $pattern The pattern without '/'
     *
     * @return string The full pattern with '/'
     */
    protected function createPattern($pattern)
    {
        $numVer = '([0-9]+|x|\*)';
        $numVer2 = '(' . $numVer . '\.' . $numVer . ')';
        $numVer3 = '(' . $numVer . '\.' . $numVer . '\.' . $numVer . ')';

        return '/^' . '(' . $numVer . '|' . $numVer2 . '|' . $numVer3 . ')' . $pattern . '/';
    }

    /**
     * Converts the version metadata.
     *
     * @param string $version
     *
     * @return string
     */
    protected function convertVersionMetadata($version)
    {
        if (preg_match_all($this->createPattern('([a-z]+|(\-|\+)[a-z]+|(\-|\+)[0-9]+)'),
            $version, $matches, PREG_OFFSET_CAPTURE)) {
            list($type, $version, $end) = $this->cleanVersion($version, $matches);
            list($version, $patchVersion) = $this->matchVersion($version, $type);

            $matches = array();
            $hasPatchNumber = preg_match('/[0-9]+|\.[0-9]+$/', $end, $matches);
            $end = $hasPatchNumber ? $matches[0] : '1';

            if ($patchVersion) {
                $version .= $end;
            }
        }

        return $version;
    }

    /**
     * Clean the raw version.
     *
     * @param string $version The version
     * @param array  $matches The match of pattern asset version
     *
     * @return array The list of $type, $version and $end
     */
    protected function cleanVersion($version, array $matches)
    {
        $end = substr($version, strlen($matches[1][0][0]));
        $version = $matches[1][0][0] . '-';

        $matches = array();
        if (preg_match('/^(\-|\+)/', $end, $matches)) {
            $end = substr($end, 1);
        }

        $matches = array();
        preg_match('/^[a-z]+/', $end, $matches);
        $type = isset($matches[0]) ? VersionParser::normalizeStability($matches[0]) : null;
        $end = substr($end, strlen($type));

        return array($type, $version, $end);
    }

    /**
     * Match the version.
     *
     * @param string $version
     * @param string $type
     *
     * @return array The list of $version and $patchVersion
     */
    protected function matchVersion($version, $type)
    {
        $patchVersion = true;

        switch ($type) {
            case 'alpha':
            case 'beta':
            case 'RC':
                break;
            case 'dev':
                $patchVersion = false;
                break;
            case 'a':
                $type = 'alpha';
                break;
            case 'b':
            case 'pre':
                $type = 'beta';
                break;
            default:
                $type = 'patch';
                break;
        }

        $version .= $type;

        return array($version, $patchVersion);
    }

    /**
     * Clean the raw range.
     *
     * @param string $range
     *
     * @return string
     */
    protected function cleanRange($range)
    {
        foreach (array('<', '>', '=', '~', '^', '||') as $character) {
            $range = str_replace($character . ' ', $character, $range);
        }

        return str_replace(' ||', '||', $range);
    }

    /**
     * Match the range.
     *
     * @param string $range The range cleaned
     *
     * @return string The range
     */
    protected function matchRange($range)
    {
        $pattern = '/(\ -\ )|(<)|(>)|(=)|(\|\|)|(\ )|(,)|(\~)|(\^)/';
        $matches = preg_split($pattern, $range, -1, PREG_SPLIT_DELIM_CAPTURE);
        $special = null;

        foreach ($matches as $i => $match) {
            if (' - ' === $match) {
                $matches[$i - 1] = '>=' . $matches[$i - 1];
                $matches[$i] = ',<=';
            } elseif (in_array($match, array('', '<', '>', '=', ','))) {
                continue;
            } elseif ('~' === $match) {
                $special = $match;
                $matches[$i] = '';
            } elseif ('^' === $match) {
                $matches[$i] = '~';
            } elseif (' ' === $match) {
                $matches[$i] = ',';
            } elseif ('||' === $match) {
                $matches[$i] = '|';
            } elseif ('~' === $special) {
                $newMatch = '>='.$this->convertVersion($match).',<';
                $exp = explode('.', $match);
                $upVersion = isset($exp[0]) ? $exp[0] : '0';

                if (isset($exp[1])) {
                    $upVersion .= '.' . ($exp[1] + 1);
                } else {
                    $upVersion .= '.1';
                }

                $newMatch .= $this->convertVersion($upVersion);
                $matches[$i] = $newMatch;
                $special = null;
            } else {
                $matches[$i] = $this->convertVersion($match);
                $special = null;
            }
        }

        return implode('', $matches);
    }
}
