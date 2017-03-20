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
 */
class NpmPackageUtilTest extends AbstractPackageConverterTest
{
    public function testConvertName()
    {
        $packageName = '@vendor/package';
        $expected = 'vendor--package';

        $this->assertSame($expected, NpmPackageUtil::convertName($packageName));
    }

    public function testRevertName()
    {
        $packageName = 'vendor--package';
        $expected = '@vendor/package';

        $this->assertSame($expected, NpmPackageUtil::revertName($packageName));
    }
}
