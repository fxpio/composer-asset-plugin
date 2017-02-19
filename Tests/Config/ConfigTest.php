<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Composer;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Package\RootPackageInterface;
use Fxp\Composer\AssetPlugin\Config\ConfigBuilder;

/**
 * Tests for the plugin config.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Composer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $composer;

    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $composerConfig;

    /**
     * @var IOInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $io;

    /**
     * @var RootPackageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $package;

    protected function setUp()
    {
        $this->composer = $this->getMockBuilder(Composer::class)->disableOriginalConstructor()->getMock();
        $this->composerConfig = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $this->io = $this->getMockBuilder(IOInterface::class)->getMock();
        $this->package = $this->getMockBuilder(RootPackageInterface::class)->getMock();

        $this->composer->expects($this->any())
            ->method('getPackage')
            ->willReturn($this->package);

        $this->composer->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->composerConfig);
    }

    public function getDataForGetConfig()
    {
        return array(
            array('foo', 42, 42),
            array('bar', 'foo', 'empty'),
            array('baz', false, true),
            array('repositories', 42, 0),
            array('global-composer-foo', 90, 0),
            array('global-composer-bar', 70, 0),
            array('global-config-foo', 23, 0),
        );
    }

    /**
     * @dataProvider getDataForGetConfig
     *
     * @param string     $key      The key
     * @param mixed      $expected The expected value
     * @param mixed|null $default  The default value
     */
    public function testGetConfig($key, $expected, $default = null)
    {
        $globalPath = realpath(__DIR__.'/../Fixtures/package/global');
        $this->composerConfig->expects($this->any())
            ->method('has')
            ->with('home')
            ->willReturn(true);

        $this->composerConfig->expects($this->any())
            ->method('get')
            ->with('home')
            ->willReturn($globalPath);

        $this->package->expects($this->any())
            ->method('getExtra')
            ->willReturn(array(
                'asset-baz' => false,
                'asset-repositories' => 42,
            ));

        $this->package->expects($this->any())
            ->method('getConfig')
            ->willReturn(array(
                'fxp-asset' => array(
                    'bar' => 'foo',
                    'baz' => false,
                ),
            ));

        if (0 === strpos($key, 'global-')) {
            $this->io->expects($this->atLeast(2))
                ->method('isDebug')
                ->willReturn(true);

            $this->io->expects($this->at(1))
                ->method('writeError')
                ->with(sprintf('Loading fxp-asset config in file %s/composer.json', $globalPath));
            $this->io->expects($this->at(3))
                ->method('writeError')
                ->with(sprintf('Loading fxp-asset config in file %s/config.json', $globalPath));
        }

        $config = ConfigBuilder::build($this->composer, $this->io);

        $this->assertSame($expected, $config->get($key, $default));
    }

    public function testValidateConfig()
    {
        $deprecated = array(
            'asset-installer-paths' => 'deprecated',
            'asset-ignore-files' => 'deprecated',
            'asset-private-bower-registries' => 'deprecated',
            'asset-pattern-skip-version' => 'deprecated',
            'asset-optimize-with-installed-packages' => 'deprecated',
            'asset-optimize-with-conjunctive' => 'deprecated',
            'asset-repositories' => 'deprecated',
            'asset-registry-options' => 'deprecated',
            'asset-vcs-driver-options' => 'deprecated',
            'asset-main-files' => 'deprecated',
        );

        $this->package->expects($this->any())
            ->method('getExtra')
            ->willReturn($deprecated);

        foreach (array_keys($deprecated) as $i => $option) {
            $this->io->expects($this->at($i))
                ->method('write')
                ->with('<warning>The "extra.'.$option.'" option is deprecated, use the "config.fxp-asset.'.substr($option, 6).'" option</warning>');
        }

        ConfigBuilder::validate($this->io, $this->package);
    }
}
