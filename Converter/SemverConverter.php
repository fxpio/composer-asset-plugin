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
     * {@inheritdoc}
     */
    public function convertRange($range)
    {
        foreach (array('<', '>', '=', '~', '^', '||') as $character) {
            $range = str_replace($character . ' ', $character, $range);
        }
        $range = str_replace(' ||', '||', $range);

        $pattern = '/(\ -\ )|(<)|(>)|(=)|(\|\|)|(\ )|(,)|(\~)|(\^)/';
        $matches = preg_split($pattern, $range, -1, PREG_SPLIT_DELIM_CAPTURE);
        $special = null;

        foreach ($matches as $i => $match) {
            switch ($match) {
                case ' - ':
                    $matches[$i - 1] = '>=' . $matches[$i - 1];
                    $matches[$i] = ',<=';
                    break;
                case '';
                case '<';
                case '>';
                case '=';
                case ',';
                    break;
                case '~';
                    $special = $match;
                    $matches[$i] = '';
                    break;
                case '^':
                    $matches[$i] = '~';
                    break;
                case ' ':
                    $matches[$i] = ',';
                    break;
                case '||':
                    $matches[$i] = '|';
                    break;
                default:
                    if ('~' === $special) {
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
                    } else {
                        $matches[$i] = $this->convertVersion($match);
                    }
                    $special = null;
                    break;
            }
        }

        return implode('', $matches);
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
        $numVer = '([0-9]+|\x|\*)';
        $numVer2 = '(' . $numVer . '\.' . $numVer . ')';
        $numVer3 = '(' . $numVer . '\.' . $numVer . '\.' . $numVer . ')';

        return '/^' . '(' . $numVer . '|' . $numVer2 . '|' . $numVer3 . ')' . $pattern . '/';
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
}
