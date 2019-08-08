<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Converter;

use Fxp\Composer\AssetPlugin\Converter\NpmPackageUtil;

/**
 * Tests of npm package util.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class NpmPackageUtilTest extends AbstractPackageConverterTest
{
    public function testConvertName()
    {
        $packageName = '@vendor/package';
        $expected = 'vendor--package';

        static::assertSame($expected, NpmPackageUtil::convertName($packageName));
    }

    public function testRevertName()
    {
        $packageName = 'vendor--package';
        $expected = '@vendor/package';

        static::assertSame($expected, NpmPackageUtil::revertName($packageName));
    }

    public function getLicenses()
    {
        return array(
            array(array('MIT'), array('MIT')),
            array(array('type' => 'MIT'), array('MIT')),
            array(array('name' => 'MIT'), array('MIT')),
            array(array(array('type' => 'MIT')), array('MIT')),
            array(array(array('name' => 'MIT')), array('MIT')),
        );
    }

    /**
     * @dataProvider getLicenses
     *
     * @param array|string $licenses
     * @param array|string $expected
     */
    public function testLicenses($licenses, $expected)
    {
        static::assertSame($expected, NpmPackageUtil::convertLicenses($licenses));
    }
}
