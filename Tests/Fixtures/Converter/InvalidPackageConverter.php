<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Fixtures\Converter;

use Fxp\Composer\AssetPlugin\Converter\AbstractPackageConverter;

/**
 * Fixture for invalid package converter tests.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class InvalidPackageConverter extends AbstractPackageConverter
{
    /**
     * {@inheritdoc}
     */
    public function convert(array $data, array &$vcsRepos = array())
    {
        $keys = array(
            'name' => array(null, function ($value) {
                return $value;
            }),
        );

        return $this->convertData($data, $keys, array(), array());
    }
}
