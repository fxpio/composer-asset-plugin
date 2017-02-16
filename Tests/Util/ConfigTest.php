<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Util;

use Composer\IO\IOInterface;
use Composer\Package\RootPackageInterface;
use Fxp\Composer\AssetPlugin\Util\Config;

/**
 * Tests for config.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class ConfigTest extends \PHPUnit_Framework_TestCase
{
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
        $this->io = $this->getMockBuilder(IOInterface::class)->getMock();
        $this->package = $this->getMockBuilder(RootPackageInterface::class)->getMock();
    }

    public function getDataForGetConfig()
    {
        return array(
            array('foo', 42, 42),
            array('bar', 'foo', 'empty'),
            array('baz', false, true),
            array('old', 42, 0),
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
        $this->package->expects($this->any())
            ->method('getExtra')
            ->willReturn(array(
                'asset-baz' => false,
                'asset-old' => 42,
            ));

        $this->package->expects($this->any())
            ->method('getConfig')
            ->willReturn(array(
                'fxp-asset' => array(
                    'bar' => 'foo',
                    'baz' => false,
                ),
            ));

        $this->assertSame($expected, Config::get($this->package, $key, $default));
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

        Config::validate($this->io, $this->package);
    }
}
