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
 */
class BowerPackageConverterTest extends AbstractPackageConverterTest
{
    protected function setUp()
    {
        parent::setUp();

        /* @var AssetTypeInterface $type */
        $type = $this->type;
        $this->converter = new BowerPackageConverter($type);
        $this->asset = json_decode(file_get_contents(__DIR__.'/../Fixtures/package/bower.json'), true);
    }

    public function testConvert()
    {
        $composer = $this->converter->convert($this->asset);

        $this->assertArrayHasKey('name', $composer);
        $this->assertSame('ASSET/'.$this->asset['name'], $composer['name']);

        $this->assertArrayHasKey('type', $composer);
        $this->assertSame('bower-asset-library', $composer['type']);

        $this->assertArrayHasKey('description', $composer);
        $this->assertSame($this->asset['description'], $composer['description']);

        $this->assertArrayHasKey('version', $composer);
        $this->assertSame('VERSION_CONVERTED', $composer['version']);

        $this->assertArrayHasKey('keywords', $composer);
        $this->assertSame($this->asset['keywords'], $composer['keywords']);

        $this->assertArrayHasKey('require', $composer);
        $this->assertSame(array(
            'ASSET/library1' => 'VERSION_RANGE_CONVERTED',
            'ASSET/library2' => 'VERSION_RANGE_CONVERTED',
            'ASSET/library2[0.9.0]' => 'VERSION_RANGE_CONVERTED',
        ), $composer['require']);

        $this->assertArrayHasKey('require-dev', $composer);
        $this->assertSame(array(
                'ASSET/dev-library1' => 'VERSION_RANGE_CONVERTED',
                'ASSET/dev-library2' => 'VERSION_RANGE_CONVERTED',
                'ASSET/dev-library2[0.9.0]' => 'VERSION_RANGE_CONVERTED',
        ), $composer['require-dev']);

        $this->assertArrayHasKey('license', $composer);
        $this->assertSame($this->asset['license'], $composer['license']);

        $this->assertArrayHasKey('bin', $composer);
        $this->assertSame($this->asset['bin'], $composer['bin']);

        $this->assertArrayHasKey('extra', $composer);

        $this->assertArrayHasKey('bower-asset-main', $composer['extra']);
        $this->assertSame($this->asset['main'], $composer['extra']['bower-asset-main']);

        $this->assertArrayHasKey('bower-asset-ignore', $composer['extra']);
        $this->assertSame($this->asset['ignore'], $composer['extra']['bower-asset-ignore']);

        $this->assertArrayHasKey('bower-asset-private', $composer['extra']);
        $this->assertSame($this->asset['private'], $composer['extra']['bower-asset-private']);

        $this->assertArrayNotHasKey('homepage', $composer);
        $this->assertArrayNotHasKey('time', $composer);
        $this->assertArrayNotHasKey('authors', $composer);
        $this->assertArrayNotHasKey('support', $composer);
        $this->assertArrayNotHasKey('conflict', $composer);
        $this->assertArrayNotHasKey('replace', $composer);
        $this->assertArrayNotHasKey('provide', $composer);
        $this->assertArrayNotHasKey('suggest', $composer);
        $this->assertArrayNotHasKey('autoload', $composer);
        $this->assertArrayNotHasKey('autoload-dev', $composer);
        $this->assertArrayNotHasKey('include-path', $composer);
        $this->assertArrayNotHasKey('target-dir', $composer);
        $this->assertArrayNotHasKey('archive', $composer);
    }
}
