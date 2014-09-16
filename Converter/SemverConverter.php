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
        if ('latest' === $version) {
            return 'default';
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

        foreach ($matches as $i => $match) {
            if (' - ' === $match) {
                $matches[$i - 1] = '>=' . $matches[$i - 1];
                $matches[$i] = ',<=';
            } elseif (in_array($match, array('', '<', '>', '=', ','))) {
                $replace = in_array($match, array('<', '>')) ? $match : $replace;
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
                $matches[$i] = $this->replaceSpecialRange($match);
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

        return implode('', $matches);
    }

    /**
     * Replaces the special range "~".
     *
     * @param string $match The match version
     *
     * @return string the new match version
     */
    protected function replaceSpecialRange($match)
    {
        $newMatch = $this->convertVersion($match);
        $newMatch = '>='.SemverUtil::replaceAlias($newMatch, '>').',<';
        $exp = explode('.', $match);
        $upVersion = isset($exp[0]) ? $exp[0] : '0';

        if (isset($exp[1])) {
            $upVersion .= '.' . ($exp[1] + 1);
        } else {
            $upVersion .= '.1';
        }

        $newMatch .= $this->convertVersion($upVersion);

        return $newMatch;
    }
}
