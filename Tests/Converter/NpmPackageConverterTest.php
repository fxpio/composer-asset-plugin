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

use Fxp\Composer\AssetPlugin\Converter\NpmPackageConverter;
use Fxp\Composer\AssetPlugin\Converter\NpmPackageUtil;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Tests of npm package converter.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class NpmPackageConverterTest extends AbstractPackageConverterTest
{
    protected function setUp()
    {
        parent::setUp();

        /** @var AssetTypeInterface $type */
        $type = $this->type;
        $this->converter = new NpmPackageConverter($type);
        $this->asset = $this->loadPackage();
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

        static::assertArrayHasKey('homepage', $composer);
        static::assertSame($this->asset['homepage'], $composer['homepage']);

        static::assertArrayHasKey('license', $composer);
        static::assertSame($this->asset['license'], $composer['license']);

        static::assertArrayHasKey('authors', $composer);
        static::assertSame(array_merge(array($this->asset['author']), $this->asset['contributors']), $composer['authors']);

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
            'ASSET/library21' => '1 || 2',
        ), $composer['require']);

        static::assertArrayNotHasKey('require-dev', $composer);

        static::assertArrayHasKey('bin', $composer);
        static::assertInternalType('array', $composer['bin']);
        static::assertSame($this->asset['bin'], $composer['bin'][0]);

        static::assertArrayHasKey('extra', $composer);

        static::assertArrayHasKey('npm-asset-bugs', $composer['extra']);
        static::assertSame($this->asset['bugs'], $composer['extra']['npm-asset-bugs']);

        static::assertArrayHasKey('npm-asset-files', $composer['extra']);
        static::assertSame($this->asset['files'], $composer['extra']['npm-asset-files']);

        static::assertArrayHasKey('npm-asset-main', $composer['extra']);
        static::assertSame($this->asset['main'], $composer['extra']['npm-asset-main']);

        static::assertArrayHasKey('npm-asset-man', $composer['extra']);
        static::assertSame($this->asset['man'], $composer['extra']['npm-asset-man']);

        static::assertArrayHasKey('npm-asset-directories', $composer['extra']);
        static::assertSame($this->asset['directories'], $composer['extra']['npm-asset-directories']);

        static::assertArrayHasKey('npm-asset-repository', $composer['extra']);
        static::assertSame($this->asset['repository'], $composer['extra']['npm-asset-repository']);

        static::assertArrayHasKey('npm-asset-scripts', $composer['extra']);
        static::assertSame($this->asset['scripts'], $composer['extra']['npm-asset-scripts']);

        static::assertArrayHasKey('npm-asset-config', $composer['extra']);
        static::assertSame($this->asset['config'], $composer['extra']['npm-asset-config']);

        static::assertArrayHasKey('npm-asset-bundled-dependencies', $composer['extra']);
        static::assertSame($this->asset['bundledDependencies'], $composer['extra']['npm-asset-bundled-dependencies']);

        static::assertArrayHasKey('npm-asset-optional-dependencies', $composer['extra']);
        static::assertSame($this->asset['optionalDependencies'], $composer['extra']['npm-asset-optional-dependencies']);

        static::assertArrayHasKey('npm-asset-engines', $composer['extra']);
        static::assertSame($this->asset['engines'], $composer['extra']['npm-asset-engines']);

        static::assertArrayHasKey('npm-asset-engine-strict', $composer['extra']);
        static::assertSame($this->asset['engineStrict'], $composer['extra']['npm-asset-engine-strict']);

        static::assertArrayHasKey('npm-asset-os', $composer['extra']);
        static::assertSame($this->asset['os'], $composer['extra']['npm-asset-os']);

        static::assertArrayHasKey('npm-asset-cpu', $composer['extra']);
        static::assertSame($this->asset['cpu'], $composer['extra']['npm-asset-cpu']);

        static::assertArrayHasKey('npm-asset-prefer-global', $composer['extra']);
        static::assertSame($this->asset['preferGlobal'], $composer['extra']['npm-asset-prefer-global']);

        static::assertArrayHasKey('npm-asset-private', $composer['extra']);
        static::assertSame($this->asset['private'], $composer['extra']['npm-asset-private']);

        static::assertArrayHasKey('npm-asset-publish-config', $composer['extra']);
        static::assertSame($this->asset['publishConfig'], $composer['extra']['npm-asset-publish-config']);

        static::assertArrayNotHasKey('time', $composer);
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

    public function testConvertWithScope()
    {
        $this->asset = $this->loadPackage('npm-scope.json');
        $composer = $this->converter->convert($this->asset);

        static::assertArrayHasKey('name', $composer);
        static::assertSame('ASSET/scope--test', $composer['name']);

        static::assertArrayHasKey('require', $composer);
        static::assertSame(array(
            'ASSET/scope--library1' => '>= 1.0.0',
            'ASSET/scope2--library2' => '>= 1.0.0',
        ), $composer['require']);

        static::assertArrayNotHasKey('require-dev', $composer);
    }

    public function getConvertDistData()
    {
        return array(
            array(array('type' => null), array()),
            array(array('foo' => 'http://example.com'), array()), // unknown downloader type
            array(array('gzip' => 'http://example.com'), array('type' => 'gzip', 'url' => 'https://example.com')),
            array(array('tarball' => 'http://example.com'), array('type' => 'tar', 'url' => 'https://example.com')),
            array(
                array('shasum' => 'abcdef0123456789abcdef0123456789abcdef01'),
                array('shasum' => 'abcdef0123456789abcdef0123456789abcdef01'),
            ),
        );
    }

    /**
     * @dataProvider getConvertDistData
     *
     * @param array $value  The value must be converted
     * @param array $result The result of convertion
     */
    public function testConvertDist($value, $result)
    {
        static::assertSame($result, NpmPackageUtil::convertDist($value));
    }

    /**
     * Load the package.
     *
     * @param string $package The package file name
     *
     * @return array
     */
    private function loadPackage($package = 'npm.json')
    {
        return (array) json_decode(file_get_contents(__DIR__.'/../Fixtures/package/'.$package), true);
    }
}
