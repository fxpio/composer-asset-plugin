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
            return ('latest' === $version ? 'default || ' : '').'*';
        }

        $version = str_replace('–', '-', $version);
        $prefix = preg_match('/^[a-z]/', $version) && 0 !== strpos($version, 'dev-') ? substr($version, 0, 1) : '';
        $version = substr($version, strlen($prefix));
        $version = SemverUtil::convertVersionMetadata($version);
        $version = SemverUtil::convertDateVersion($version);

        return $prefix.$version;
    }

    /**
     * {@inheritdoc}
     */
    public function convertRange($range)
    {
        $range = $this->cleanRange(strtolower($range));

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
        foreach (array('<', '>', '=', '~', '^', '||', '&&') as $character) {
            $range = str_replace($character.' ', $character, $range);
        }

        $range = preg_replace('/(?:[vV])(\d+)/', '${1}', $range);
        $range = str_replace(' ||', '||', $range);
        $range = str_replace(array(' &&', '&&'), ',', $range);

        return $range;
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
                $match = '=' === $match ? 'EQUAL' : $match;
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
        if (' - ' === $match) {
            $matches[$i - 1] = '>='.str_replace(array('*', 'x', 'X'), '0', $matches[$i - 1]);

            if (false !== strpos($matches[$i + 1], '.') && strpos($matches[$i + 1], '*') === false
                    && strpos($matches[$i + 1], 'x') === false && strpos($matches[$i + 1], 'X') === false) {
                $matches[$i] = ',<=';
            } else {
                $matches[$i] = ',<';
                $special = ',<~';
            }
        } else {
            $this->matchRangeTokenStep2($i, $match, $matches, $special, $replace);
        }
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
        if (in_array($match, array('', '<', '>', '=', ','))) {
            $replace = in_array($match, array('<', '>')) ? $match : $replace;
            $matches[$i] = '~' === $special && in_array($replace, array('<', '>')) ? '' : $matches[$i];
        } elseif ('~' === $match) {
            $special = $match;
        } elseif (in_array($match, array('EQUAL', '^'))) {
            $special = $match;
            $matches[$i] = '';
        } else {
            $this->matchRangeTokenStep3($i, $match, $matches, $special, $replace);
        }
    }

    /**
     * Step3: Converts the token of the matched range.
     *
     * @param int         $i
     * @param string      $match
     * @param array       $matches
     * @param string|null $special
     * @param string|null $replace
     */
    protected function matchRangeTokenStep3($i, $match, array &$matches, &$special, &$replace)
    {
        if (' ' === $match) {
            $matches[$i] = ',';
        } elseif ('||' === $match) {
            $matches[$i] = '|';
        } elseif (in_array($special, array('^'))) {
            $matches[$i] = SemverRangeUtil::replaceSpecialRange($this, $match);
            $special = null;
        } else {
            $this->matchRangeTokenStep4($i, $match, $matches, $special, $replace);
        }
    }

    /**
     * Step4: Converts the token of the matched range.
     *
     * @param int         $i
     * @param string      $match
     * @param array       $matches
     * @param string|null $special
     * @param string|null $replace
     */
    protected function matchRangeTokenStep4($i, $match, array &$matches, &$special, &$replace)
    {
        if ($special === ',<~') {
            // Version range contains x in last place.
            $match .= (false === strpos($match, '.') ? '.x' : '');
            $version = explode('.', $match);
            $change = count($version) - 2;
            $version[$change] = (int) ($version[$change]) + 1;
            $match = str_replace(array('*', 'x', 'X'), '0', implode('.', $version));
        } elseif (null === $special && $i === 0 && false === strpos($match, '.') && is_numeric($match)) {
            $match = isset($matches[$i + 1]) && (' - ' === $matches[$i + 1] || '-' === $matches[$i + 1])
                ? $match
                : '~'.$match;
        } else {
            $match = '~' === $special ? str_replace(array('*', 'x', 'X'), '0', $match) : $match;
        }

        $this->matchRangeTokenStep5($i, $match, $matches, $special, $replace);
    }

    /**
     * Step5: Converts the token of the matched range.
     *
     * @param int         $i
     * @param string      $match
     * @param array       $matches
     * @param string|null $special
     * @param string|null $replace
     */
    protected function matchRangeTokenStep5($i, $match, array &$matches, &$special, &$replace)
    {
        $matches[$i] = $this->convertVersion($match);
        $matches[$i] = $replace
            ? SemverUtil::replaceAlias($matches[$i], $replace)
            : $matches[$i];
        $matches[$i] .= '~' === $special && in_array($replace, array('<', '>'))
            ? ','.$replace.$matches[$i]
            : '';
        $special = null;
        $replace = null;
    }
}
