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

use Fxp\Composer\AssetPlugin\Converter\BowerPackageConverter;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Tests of bower package converter.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class BowerPackageConverterTest extends AbstractPackageConverterTest
{
    protected function setUp()
    {
        parent::setUp();

        /** @var AssetTypeInterface $type */
        $type = $this->type;
        $this->converter = new BowerPackageConverter($type);
        $this->asset = (array) json_decode(file_get_contents(__DIR__.'/../Fixtures/package/bower.json'), true);
    }

    public function testConvert()
    {
        $composer = $this->converter->convert($this->asset);

        static::assertArrayHasKey('name', $composer);
        static::assertSame('ASSET/'.$this->asset['name'], $composer['name']);

        static::assertArrayHasKey('type', $composer);
        static::assertSame('ASSET_TYPE', $composer['type']);

        static::assertArrayHasKey('description', $composer);
        static::assertSame($this->asset['description'], $composer['description']);

        static::assertArrayHasKey('version', $composer);
        static::assertSame('1.0.0-pre', $composer['version']);

        static::assertArrayHasKey('keywords', $composer);
        static::assertSame($this->asset['keywords'], $composer['keywords']);

        static::assertArrayHasKey('require', $composer);
        static::assertSame(array(
            'ASSET/library1' => '>= 1.0.0',
            'ASSET/library2' => '>= 1.0.0',
            'ASSET/library2-0.9.0' => '0.9.0',
            'ASSET/library3' => '*',
            'ASSET/library4' => '1.2.3',
            'ASSET/library5' => 'dev-default#0a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b',
            'ASSET/library6' => 'dev-branch',
            'ASSET/library7' => 'dev-1.2.* || 1.2.*',
            'ASSET/library8' => 'dev-1.2.x || 1.2.x',
            'ASSET/library9' => 'dev-master',
            'ASSET/library10' => '1.0.0',
            'ASSET/library11' => '*',
            'ASSET/library12' => '>=1 <2',
            'ASSET/library13' => '>=1 <2',
            'ASSET/library14' => '*',
            'ASSET/library15' => '*',
            'ASSET/library16' => '>=1 <2',
            'ASSET/test-library17-file' => '*',
            'ASSET/test-library18-file' => '1.2.3',
            'ASSET/test-library19-file' => '*',
            'ASSET/test-library20-file' => '*',
        ), $composer['require']);

        static::assertArrayNotHasKey('require-dev', $composer);

        static::assertArrayHasKey('license', $composer);
        static::assertSame($this->asset['license'], $composer['license']);

        static::assertArrayHasKey('bin', $composer);
        static::assertSame($this->asset['bin'], $composer['bin']);

        static::assertArrayHasKey('extra', $composer);

        static::assertArrayHasKey('bower-asset-main', $composer['extra']);
        static::assertSame($this->asset['main'], $composer['extra']['bower-asset-main']);

        static::assertArrayHasKey('bower-asset-ignore', $composer['extra']);
        static::assertSame($this->asset['ignore'], $composer['extra']['bower-asset-ignore']);

        static::assertArrayHasKey('bower-asset-private', $composer['extra']);
        static::assertSame($this->asset['private'], $composer['extra']['bower-asset-private']);

        static::assertArrayNotHasKey('homepage', $composer);
        static::assertArrayNotHasKey('time', $composer);
        static::assertArrayNotHasKey('authors', $composer);
        static::assertArrayNotHasKey('support', $composer);
        static::assertArrayNotHasKey('conflict', $composer);
        static::assertArrayNotHasKey('replace', $composer);
        static::assertArrayNotHasKey('provide', $composer);
        static::assertArrayNotHasKey('suggest', $composer);
        static::assertArrayNotHasKey('autoload', $composer);
        static::assertArrayNotHasKey('autoload-dev', $composer);
        static::assertArrayNotHasKey('include-path', $composer);
        static::assertArrayNotHasKey('target-dir', $composer);
        static::assertArrayNotHasKey('archive', $composer);
    }
}
