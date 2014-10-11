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

use Fxp\Composer\AssetPlugin\Converter\SemverConverter;
use Fxp\Composer\AssetPlugin\Converter\VersionConverterInterface;

/**
 * Tests for the conversion of Semver syntax to composer syntax.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class SemverConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var VersionConverterInterface
     */
    protected $converter;

    protected function setUp()
    {
        $this->converter = new SemverConverter();
    }

    protected function tearDown()
    {
        $this->converter = null;
    }

    /**
     * @dataProvider getTestVersions
     */
    public function testConverter($semver, $composer)
    {
        $this->assertEquals($composer, $this->converter->convertVersion($semver));

        if (!ctype_alpha($semver) && !in_array($semver, array(null, ''))) {
            $this->assertEquals('v' . $composer, $this->converter->convertVersion('v' . $semver));
        }
    }

    public function getTestVersions()
    {
        return array(
            array('1.2.3', '1.2.3'),
            array('1.2.3alpha', '1.2.3-alpha1'),
            array('1.2.3-alpha', '1.2.3-alpha1'),
            array('1.2.3a', '1.2.3-alpha1'),
            array('1.2.3a1', '1.2.3-alpha1'),
            array('1.2.3-a', '1.2.3-alpha1'),
            array('1.2.3-a1', '1.2.3-alpha1'),
            array('1.2.3b', '1.2.3-beta1'),
            array('1.2.3b1', '1.2.3-beta1'),
            array('1.2.3-b', '1.2.3-beta1'),
            array('1.2.3-b1', '1.2.3-beta1'),
            array('1.2.3beta', '1.2.3-beta1'),
            array('1.2.3-beta', '1.2.3-beta1'),
            array('1.2.3beta1', '1.2.3-beta1'),
            array('1.2.3-beta1', '1.2.3-beta1'),
            array('1.2.3rc1', '1.2.3-RC1'),
            array('1.2.3-rc1', '1.2.3-RC1'),
            array('1.2.3rc2', '1.2.3-RC2'),
            array('1.2.3-rc2', '1.2.3-RC2'),
            array('1.2.3rc.2', '1.2.3-RC.2'),
            array('1.2.3-rc.2', '1.2.3-RC.2'),
            array('1.2.3+0', '1.2.3-patch0'),
            array('1.2.3-0', '1.2.3-patch0'),
            array('1.2.3pre', '1.2.3-beta1'),
            array('1.2.3-pre', '1.2.3-beta1'),
            array('1.2.3dev', '1.2.3-dev'),
            array('1.2.3-dev', '1.2.3-dev'),
            array('1.2.3+build2012', '1.2.3-patch2012'),
            array('1.2.3-build2012', '1.2.3-patch2012'),
            array('1.2.3+build.2012', '1.2.3-patch.2012'),
            array('1.2.3-build.2012', '1.2.3-patch.2012'),
            array('latest', '*'),
            array(null, '*'),
            array('', '*'),
        );
    }

    /**
     * @dataProvider getTestRanges
     */
    public function testRangeConverter($semver, $composer)
    {
        $this->assertEquals($composer, $this->converter->convertRange($semver));
    }

    public function getTestRanges()
    {
        return array(
            array('>1.2.3', '>1.2.3'),
            array('<1.2.3', '<1.2.3'),
            array('>=1.2.3', '>=1.2.3'),
            array('<=1.2.3', '<=1.2.3'),
            array('~1.2.3', '>=1.2.3,<1.3'),
            array('~1', '>=1,<1.1'),
            array('^1.2.3', '>=1.2.3,<2.0'),
            array('^1.2', '>=1.2,<2.0'),
            array('>1.2.3 <2.0', '>1.2.3,<2.0'),
            array('>1.2 <2.0', '>1.2,<2.0'),
            array('>1 <2', '>1,<2'),
            array('>=1.2.3 <2.0', '>=1.2.3,<2.0'),
            array('>=1.2 <2.0', '>=1.2,<2.0'),
            array('>=1 <2', '>=1,<2'),
            array('>=1.0 <1.1 || >=1.2', '>=1.0,<1.1|>=1.2'),
            array('< 1.2.3', '<1.2.3'),
            array('> 1.2.3', '>1.2.3'),
            array('<= 1.2.3', '<=1.2.3'),
            array('>= 1.2.3', '>=1.2.3'),
            array('~ 1.2.3', '>=1.2.3,<1.3'),
            array('~ 1', '>=1,<1.1'),
            array('^ 1.2.3', '>=1.2.3,<2.0'),
            array('1.2.3 - 2.3.4', '>=1.2.3,<=2.3.4'),
            array('>=0.10.x', '>=0.10.0'),
            array('>=0.10.*', '>=0.10.0'),
            array('<=0.10.x', '<=0.10.9999999'),
            array('<=0.10.*', '<=0.10.9999999'),
            array('~1.2.x', '>=1.2.0,<1.3'),
            array('=1.2.x', '>=1.2.0,<1.3'),
        );
    }
}
