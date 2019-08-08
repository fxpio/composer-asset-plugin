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

use Fxp\Composer\AssetPlugin\Converter\PackageConverterInterface;
use Fxp\Composer\AssetPlugin\Tests\Fixtures\Converter\InvalidPackageConverter;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Abstract tests of asset package converter.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class AbstractPackageConverterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AssetTypeInterface
     */
    protected $type;

    /**
     * @var PackageConverterInterface
     */
    protected $converter;

    /**
     * @var array
     */
    protected $asset;

    protected function setUp()
    {
        $versionConverter = $this->getMockBuilder('Fxp\Composer\AssetPlugin\Converter\VersionConverterInterface')->getMock();
        $versionConverter->expects(static::any())
            ->method('convertVersion')
            ->willReturnCallback(function ($value) {
                return $value;
            })
        ;
        $versionConverter->expects(static::any())
            ->method('convertRange')
            ->willReturnCallback(function ($value) {
                return $value;
            })
        ;
        $type = $this->getMockBuilder('Fxp\Composer\AssetPlugin\Type\AssetTypeInterface')->getMock();
        $type->expects(static::any())
            ->method('getComposerVendorName')
            ->willReturn('ASSET')
        ;
        $type->expects(static::any())
            ->method('getComposerType')
            ->willReturn('ASSET_TYPE')
        ;
        $type->expects(static::any())
            ->method('getVersionConverter')
            ->willReturn($versionConverter)
        ;
        $type->expects(static::any())
            ->method('formatComposerName')
            ->willReturnCallback(function ($value) {
                return 'ASSET/'.$value;
            })
        ;

        $this->type = $type;
    }

    protected function tearDown()
    {
        $this->type = null;
        $this->converter = null;
        $this->asset = array();
    }

    /**
     * @expectedException \Fxp\Composer\AssetPlugin\Exception\InvalidArgumentException
     */
    public function testConversionWithInvalidKey()
    {
        $this->converter = new InvalidPackageConverter($this->type);

        $this->converter->convert(array(
            'name' => 'foo',
        ));
    }
}
