<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Repository;

use Composer\Package\RootPackageInterface;
use Composer\Package\Version\VersionParser;
use Fxp\Composer\AssetPlugin\Repository\VcsPackageFilter;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Tests of VCS Package Filter.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class VcsPackageFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $package;

    /**
     * @var AssetTypeInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $assetType;

    /**
     * @var VcsPackageFilter
     */
    protected $filter;

    protected function setUp()
    {
        $this->package = $this->getMock('Composer\Package\RootPackageInterface');
        $this->assetType = $this->getMock('Fxp\Composer\AssetPlugin\Type\AssetTypeInterface');

        $versionConverter = $this->getMock('Fxp\Composer\AssetPlugin\Converter\VersionConverterInterface');
        $versionConverter->expects($this->any())
            ->method('convertVersion')
            ->will($this->returnCallback(function ($value) {
                return $value;
            }));
        $this->assetType->expects($this->any())
            ->method('getVersionConverter')
            ->will($this->returnValue($versionConverter));
    }

    protected function tearDown()
    {
        $this->package = null;
        $this->assetType = null;
        $this->filter = null;
    }

    public function getDataProvider()
    {
        return array(
            array('acme/foobar', 'v1.0.0',        'stable', array(),                                    false),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0'),            true),

            array('acme/foobar', 'v1.0.0',        'RC',     array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-RC1',    'RC',     array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-beta1',  'RC',     array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-alpha1', 'RC',     array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0'),            true),

            array('acme/foobar', 'v1.0.0',        'beta',   array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-RC1',    'beta',   array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-beta1',  'beta',   array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-alpha1', 'beta',   array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0'),            true),

            array('acme/foobar', 'v1.0.0',        'alpha',  array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-RC1',    'alpha',  array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-beta1',  'alpha',  array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-alpha1', 'alpha',  array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0'),            true),

            array('acme/foobar', 'v1.0.0',        'dev',    array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-RC1',    'dev',    array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-beta1',  'dev',    array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-alpha1', 'dev',    array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-patch1', 'dev',    array('acme/foobar' => '>=1.0'),            false),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '>=1.0@stable'),     false),
            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '>=1.0@RC'),         false),
            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '>=1.0@beta'),       false),
            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '>=1.0@alpha'),      false),
            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '>=1.0@dev'),        false),
            array('acme/foobar', 'v1.0.0',        'RC',     array('acme/foobar' => '>=1.0@stable'),     false),
            array('acme/foobar', 'v1.0.0',        'RC',     array('acme/foobar' => '>=1.0@RC'),         false),
            array('acme/foobar', 'v1.0.0',        'RC',     array('acme/foobar' => '>=1.0@beta'),       false),
            array('acme/foobar', 'v1.0.0',        'RC',     array('acme/foobar' => '>=1.0@alpha'),      false),
            array('acme/foobar', 'v1.0.0',        'RC',     array('acme/foobar' => '>=1.0@dev'),        false),
            array('acme/foobar', 'v1.0.0',        'beta',   array('acme/foobar' => '>=1.0@stable'),     false),
            array('acme/foobar', 'v1.0.0',        'beta',   array('acme/foobar' => '>=1.0@RC'),         false),
            array('acme/foobar', 'v1.0.0',        'beta',   array('acme/foobar' => '>=1.0@beta'),       false),
            array('acme/foobar', 'v1.0.0',        'beta',   array('acme/foobar' => '>=1.0@alpha'),      false),
            array('acme/foobar', 'v1.0.0',        'beta',   array('acme/foobar' => '>=1.0@dev'),        false),
            array('acme/foobar', 'v1.0.0',        'alpha',  array('acme/foobar' => '>=1.0@stable'),     false),
            array('acme/foobar', 'v1.0.0',        'alpha',  array('acme/foobar' => '>=1.0@RC'),         false),
            array('acme/foobar', 'v1.0.0',        'alpha',  array('acme/foobar' => '>=1.0@beta'),       false),
            array('acme/foobar', 'v1.0.0',        'alpha',  array('acme/foobar' => '>=1.0@alpha'),      false),
            array('acme/foobar', 'v1.0.0',        'alpha',  array('acme/foobar' => '>=1.0@dev'),        false),

            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '>=1.0@stable'),     true),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '>=1.0@RC'),         false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '>=1.0@beta'),       false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '>=1.0@alpha'),      false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '>=1.0@dev'),        false),
            array('acme/foobar', 'v1.0.0-RC1',    'RC',     array('acme/foobar' => '>=1.0@stable'),     true),
            array('acme/foobar', 'v1.0.0-RC1',    'RC',     array('acme/foobar' => '>=1.0@RC'),         false),
            array('acme/foobar', 'v1.0.0-RC1',    'RC',     array('acme/foobar' => '>=1.0@beta'),       false),
            array('acme/foobar', 'v1.0.0-RC1',    'RC',     array('acme/foobar' => '>=1.0@alpha'),      false),
            array('acme/foobar', 'v1.0.0-RC1',    'RC',     array('acme/foobar' => '>=1.0@dev'),        false),
            array('acme/foobar', 'v1.0.0-RC1',    'beta',   array('acme/foobar' => '>=1.0@stable'),     true),
            array('acme/foobar', 'v1.0.0-RC1',    'beta',   array('acme/foobar' => '>=1.0@RC'),         false),
            array('acme/foobar', 'v1.0.0-RC1',    'beta',   array('acme/foobar' => '>=1.0@beta'),       false),
            array('acme/foobar', 'v1.0.0-RC1',    'beta',   array('acme/foobar' => '>=1.0@alpha'),      false),
            array('acme/foobar', 'v1.0.0-RC1',    'beta',   array('acme/foobar' => '>=1.0@dev'),        false),
            array('acme/foobar', 'v1.0.0-RC1',    'alpha',  array('acme/foobar' => '>=1.0@stable'),     true),
            array('acme/foobar', 'v1.0.0-RC1',    'alpha',  array('acme/foobar' => '>=1.0@RC'),         false),
            array('acme/foobar', 'v1.0.0-RC1',    'alpha',  array('acme/foobar' => '>=1.0@beta'),       false),
            array('acme/foobar', 'v1.0.0-RC1',    'alpha',  array('acme/foobar' => '>=1.0@alpha'),      false),
            array('acme/foobar', 'v1.0.0-RC1',    'alpha',  array('acme/foobar' => '>=1.0@dev'),        false),

            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '>=1.0@beta'),        false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '>=1.0@alpha'),       false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '>=1.0@dev'),         false),
            array('acme/foobar', 'v1.0.0-beta1',  'RC',     array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-beta1',  'RC',     array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-beta1',  'RC',     array('acme/foobar' => '>=1.0@beta'),        false),
            array('acme/foobar', 'v1.0.0-beta1',  'RC',     array('acme/foobar' => '>=1.0@alpha'),       false),
            array('acme/foobar', 'v1.0.0-beta1',  'RC',     array('acme/foobar' => '>=1.0@dev'),         false),
            array('acme/foobar', 'v1.0.0-beta1',  'beta',   array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-beta1',  'beta',   array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-beta1',  'beta',   array('acme/foobar' => '>=1.0@beta'),        false),
            array('acme/foobar', 'v1.0.0-beta1',  'beta',   array('acme/foobar' => '>=1.0@alpha'),       false),
            array('acme/foobar', 'v1.0.0-beta1',  'beta',   array('acme/foobar' => '>=1.0@dev'),         false),
            array('acme/foobar', 'v1.0.0-beta1',  'alpha',  array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-beta1',  'alpha',  array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-beta1',  'alpha',  array('acme/foobar' => '>=1.0@beta'),        false),
            array('acme/foobar', 'v1.0.0-beta1',  'alpha',  array('acme/foobar' => '>=1.0@alpha'),       false),
            array('acme/foobar', 'v1.0.0-beta1',  'alpha',  array('acme/foobar' => '>=1.0@dev'),         false),

            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '>=1.0@beta'),        true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '>=1.0@alpha'),       false),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '>=1.0@dev'),         false),
            array('acme/foobar', 'v1.0.0-alpha1', 'RC',     array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-alpha1', 'RC',     array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-alpha1', 'RC',     array('acme/foobar' => '>=1.0@beta'),        true),
            array('acme/foobar', 'v1.0.0-alpha1', 'RC',     array('acme/foobar' => '>=1.0@alpha'),       false),
            array('acme/foobar', 'v1.0.0-alpha1', 'RC',     array('acme/foobar' => '>=1.0@dev'),         false),
            array('acme/foobar', 'v1.0.0-alpha1', 'beta',   array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-alpha1', 'beta',   array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-alpha1', 'beta',   array('acme/foobar' => '>=1.0@beta'),        true),
            array('acme/foobar', 'v1.0.0-alpha1', 'beta',   array('acme/foobar' => '>=1.0@alpha'),       false),
            array('acme/foobar', 'v1.0.0-alpha1', 'beta',   array('acme/foobar' => '>=1.0@dev'),         false),
            array('acme/foobar', 'v1.0.0-alpha1', 'alpha',  array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-alpha1', 'alpha',  array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-alpha1', 'alpha',  array('acme/foobar' => '>=1.0@beta'),        true),
            array('acme/foobar', 'v1.0.0-alpha1', 'alpha',  array('acme/foobar' => '>=1.0@alpha'),       false),
            array('acme/foobar', 'v1.0.0-alpha1', 'alpha',  array('acme/foobar' => '>=1.0@dev'),         false),

            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@beta'),        true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@alpha'),       true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@dev'),         false),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@beta'),        true),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@alpha'),       true),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@dev'),         false),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@beta'),        true),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@alpha'),       true),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@dev'),         false),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@stable'),      true),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@RC'),          true),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@beta'),        true),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@alpha'),       true),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@dev'),         false),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '~1.0'),              false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '~1.0'),              true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '~1.0'),              true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '~1.0'),              true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '~1.0'),              true),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0'),               false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0'),               true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0'),               true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0'),               true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0'),               true),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '@stable'),           false),
            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '@RC'),               false),
            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '@beta'),             false),
            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '@alpha'),            false),
            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '@dev'),              false),

            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '@stable'),           true),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '@RC'),               false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '@beta'),             false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '@alpha'),            false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '@dev'),              false),

            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '@stable'),           true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '@RC'),               true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '@beta'),             false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '@alpha'),            false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '@dev'),              false),

            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '@stable'),           true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '@RC'),               true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '@beta'),             true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '@alpha'),            false),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '@dev'),              false),

            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@stable'),           true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@RC'),               true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@beta'),             true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@alpha'),            true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@dev'),              false),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0'),               false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0-RC1'),           true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0-beta1'),         true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0-alpha1'),        true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1'),        true),

            array('acme/foobar', 'v1.0.0',        'RC',     array('acme/foobar' => '1.0'),               false),
            array('acme/foobar', 'v1.0.0-RC1',    'RC',     array('acme/foobar' => '1.0-RC1'),           false),
            array('acme/foobar', 'v1.0.0-beta1',  'RC',     array('acme/foobar' => '1.0-beta1'),         true),
            array('acme/foobar', 'v1.0.0-alpha1', 'RC',     array('acme/foobar' => '1.0-alpha1'),        true),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '1.0-patch1'),        true),

            array('acme/foobar', 'v1.0.0',        'beta',   array('acme/foobar' => '1.0'),               false),
            array('acme/foobar', 'v1.0.0-RC1',    'beta',   array('acme/foobar' => '1.0-RC1'),           false),
            array('acme/foobar', 'v1.0.0-beta1',  'beta',   array('acme/foobar' => '1.0-beta1'),         false),
            array('acme/foobar', 'v1.0.0-alpha1', 'beta',   array('acme/foobar' => '1.0-alpha1'),        true),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '1.0-patch1'),        true),

            array('acme/foobar', 'v1.0.0',        'alpha',  array('acme/foobar' => '1.0'),               false),
            array('acme/foobar', 'v1.0.0-RC1',    'alpha',  array('acme/foobar' => '1.0-RC1'),           false),
            array('acme/foobar', 'v1.0.0-beta1',  'alpha',  array('acme/foobar' => '1.0-beta1'),         false),
            array('acme/foobar', 'v1.0.0-alpha1', 'alpha',  array('acme/foobar' => '1.0-alpha1'),        false),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '1.0-patch1'),        true),

            array('acme/foobar', 'v1.0.0',        'dev',    array('acme/foobar' => '1.0'),               false),
            array('acme/foobar', 'v1.0.0-RC1',    'dev',    array('acme/foobar' => '1.0-RC1'),           false),
            array('acme/foobar', 'v1.0.0-beta1',  'dev',    array('acme/foobar' => '1.0-beta1'),         false),
            array('acme/foobar', 'v1.0.0-alpha1', 'dev',    array('acme/foobar' => '1.0-alpha1'),        false),
            array('acme/foobar', 'v1.0.0-patch1', 'dev',    array('acme/foobar' => '1.0-patch1'),        false),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0@stable'),        false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0-RC1@stable'),    true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0-beta1@stable'),  true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0-alpha1@stable'), true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@stable'), true),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0@RC'),            false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0-RC1@RC'),        false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0-beta1@RC'),      true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0-alpha1@RC'),     true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@RC'),     true),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0@beta'),          false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0-RC1@beta'),      false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0-beta1@beta'),    false),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0-alpha1@beta'),   true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@beta'),   true),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0@alpha'),         false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0-RC1@alpha'),     false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0-beta1@alpha'),   false),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0-alpha1@alpha'),  false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@alpha'),  true),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0@dev'),           false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0-RC1@dev'),       false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0-beta1@dev'),     false),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0-alpha1@dev'),    false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@dev'),    false),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0@dev | 1.0.*@RC'), false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0@dev | 1.0.*@RC'), false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0@dev | 1.0.*@RC'), false),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0@dev | 1.0.*@RC'), false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0@dev | 1.0.*@RC'), false),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0 | 1.0.*@RC'),    false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0 | 1.0.*@RC'),    false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0 | 1.0.*@RC'),    true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0 | 1.0.*@RC'),    true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0 | 1.0.*@RC'),    true),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0@dev | 1.0.*'),   false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0@dev|1.0.*@RC'),  false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0@dev | 1.0.*'),   false),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0@dev | 1.0.*'),   false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0@dev | 1.0.*'),   false),

            array('acme/foobar', 'standard/1.0.0', 'stable', array('acme/foobar' => '>=1.0'),            true),
        );
    }

    /**
     * @dataProvider getDataProvider
     */
    public function testSkipVersion($packageName, $version, $minimumStability, array $rootRequires, $validSkip)
    {
        $this->init($rootRequires, $minimumStability);

        $this->assertSame($validSkip, $this->filter->skip($this->assetType, $packageName, $version));
    }

    protected function init(array $requires = array(), $minimumStability = 'stable')
    {
        $parser = new VersionParser();
        $linkRequires = $parser->parseLinks('__ROOT__', '1.0.0', 'requires', $requires);

        $this->package->expects($this->any())
            ->method('getRequires')
            ->will($this->returnValue($linkRequires));
        $this->package->expects($this->any())
            ->method('getDevRequires')
            ->will($this->returnValue(array()));
        $this->package->expects($this->any())
            ->method('getMinimumStability')
            ->will($this->returnValue($minimumStability));

        /* @var RootPackageInterface $package */
        $package = $this->package;
        $this->filter = new VcsPackageFilter($package);
    }
}
