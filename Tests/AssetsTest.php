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

use Fxp\Composer\AssetPlugin\Assets;

/**
 * Tests of assets factory.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class AssetsTest extends \PHPUnit\Framework\TestCase
{
    public function testGetTypes()
    {
        static::assertEquals(array(
            'npm',
            'bower',
        ), Assets::getTypes());
    }

    public function testDefaultGetRegistries()
    {
        static::assertEquals(array(
            'npm',
            'bower',
        ), array_keys(Assets::getDefaultRegistries()));
    }

    public function testGetVcsRepositoryDrivers()
    {
        static::assertEquals(array(
            'vcs',
            'github',
            'git-bitbucket',
            'git',
            'hg-bitbucket',
            'hg',
            'perforce',
            'svn',
            'url',
        ), array_keys(Assets::getVcsRepositoryDrivers()));
    }

    public function testGetVcsDrivers()
    {
        static::assertEquals(array(
            'github',
            'git-bitbucket',
            'git',
            'hg-bitbucket',
            'hg',
            'perforce',
            'url',
            'svn',
        ), array_keys(Assets::getVcsDrivers()));
    }

    /**
     * @expectedException \Fxp\Composer\AssetPlugin\Exception\InvalidArgumentException
     */
    public function testCreationOfInvalidType()
    {
        Assets::createType(null);
    }

    public function testCreationOfNpmAsset()
    {
        $type = Assets::createType('npm');

        static::assertInstanceOf('Fxp\Composer\AssetPlugin\Type\AssetTypeInterface', $type);
    }

    public function testCreationOfBowerAsset()
    {
        $type = Assets::createType('bower');

        static::assertInstanceOf('Fxp\Composer\AssetPlugin\Type\AssetTypeInterface', $type);
    }

    public function testCreationOfPrivateBowerAsset()
    {
        $type = Assets::createType('bower');

        static::assertInstanceOf('Fxp\Composer\AssetPlugin\Type\AssetTypeInterface', $type);
    }
}
