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
        if (in_array($version, array(null, '', 'latest'))) {
            return '*';
        }

        $prefix = preg_match('/^[a-z]/', $version) ? substr($version, 0, 1) : '';
        $version = substr($version, strlen($prefix));
        $version = SemverUtil::convertVersionMetadata($version);

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
        $replace = null;
        $first = true;

        foreach ($matches as $i => $match) {
            if ($first && '' !== $match) {
                $first = false;
                $match = '=' === $match ? '~' : $match;
            }

            $this->matchRangeToken($i, $match, $matches, $special, $replace);
        }

        return implode('', $matches);
    }

    /**
     * Converts the token of the matched range.
     *
     * @param int         $i
     * @param string      $match
     * @param array       $matches
     * @param string|null $special
     * @param string|null $replace
     */
    protected function matchRangeToken($i, $match, array &$matches, &$special, &$replace)
    {
        $matched = $this->matchRangeTokenStep1($i, $match, $matches, $special, $replace);

        if (!$matched) {
            $this->matchRangeTokenStep2($i, $match, $matches, $special, $replace);
        }
    }

    /**
     * Step1: Converts the token of the matched range.
     *
     * @param int         $i
     * @param string      $match
     * @param array       $matches
     * @param string|null $special
     * @param string|null $replace
     *
     * @return bool
     */
    protected function matchRangeTokenStep1($i, $match, array &$matches, &$special, &$replace)
    {
        $matched = true;

        if (' - ' === $match) {
            $matches[$i - 1] = '>=' . $matches[$i - 1];
            $matches[$i] = ',<=';
        } elseif (in_array($match, array('', '<', '>', '=', ','))) {
            $replace = in_array($match, array('<', '>')) ? $match : $replace;
        } elseif (in_array($match, array('~', '^'))) {
            $special = $match;
            $matches[$i] = '';
        } else {
            $matched = false;
        }

        return $matched;
    }

    /**
     * Step2: Converts the token of the matched range.
     *
     * @param int         $i
     * @param string      $match
     * @param array       $matches
     * @param string|null $special
     * @param string|null $replace
     */
    protected function matchRangeTokenStep2($i, $match, array &$matches, &$special, &$replace)
    {
        if (' ' === $match) {
            $matches[$i] = ',';
        } elseif ('||' === $match) {
            $matches[$i] = '|';
        } elseif (in_array($special, array('~', '^'))) {
            $matches[$i] = $this->replaceSpecialRange($match, '^' === $special);
            $special = null;
        } else {
            $matches[$i] = $this->convertVersion($match);
            $matches[$i] = $replace
                ? SemverUtil::replaceAlias($matches[$i], $replace)
                : $matches[$i];
            $special = null;
            $replace = null;
        }
    }

    /**
     * Replaces the special range "~".
     *
     * @param string $match        The match version
     * @param bool   $majorVersion Limit the the major version or
     *
     * @return string the new match version
     */
    protected function replaceSpecialRange($match, $majorVersion = false)
    {
        $newMatch = $this->convertVersion($match);
        $newMatch = '>='.SemverUtil::replaceAlias($newMatch, '>').',<';
        $exp = explode('.', $match);
        $upVersion = isset($exp[0]) ? $exp[0] : '0';

        if (!$majorVersion) {
            $minor = isset($exp[1]) ? (int) $exp[1] : 0;
            $upVersion .= '.' . ($minor + 1);

        } else {
            $upVersion = ((int) $upVersion + 1) . '.0';
        }

        $newMatch .= $this->convertVersion($upVersion);

        return $newMatch;
    }
}
