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

use Composer\Composer;
use Composer\Installer\InstallationManager;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Package;
use Composer\Package\RootPackageInterface;
use Composer\Repository\InstalledFilesystemRepository;
use Fxp\Composer\AssetPlugin\Config\ConfigBuilder;
use Fxp\Composer\AssetPlugin\Package\Version\VersionParser;
use Fxp\Composer\AssetPlugin\Repository\VcsPackageFilter;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Tests of VCS Package Filter.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class VcsPackageFilterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Composer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $composer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RootPackageInterface
     */
    protected $package;

    /**
     * @var InstallationManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $installationManager;

    /**
     * @var null|InstalledFilesystemRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $installedRepository;

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
        $this->composer = $this->getMockBuilder('Composer\Composer')->disableOriginalConstructor()->getMock();
        $this->package = $this->getMockBuilder('Composer\Package\RootPackageInterface')->getMock();
        $this->assetType = $this->getMockBuilder('Fxp\Composer\AssetPlugin\Type\AssetTypeInterface')->getMock();

        $versionConverter = $this->getMockBuilder('Fxp\Composer\AssetPlugin\Converter\VersionConverterInterface')->getMock();
        $versionConverter->expects(static::any())
            ->method('convertVersion')
            ->willReturnCallback(function ($value) {
                return $value;
            })
        ;
        $this->assetType->expects(static::any())
            ->method('getVersionConverter')
            ->willReturn($versionConverter)
        ;

        $this->installationManager = $this->getMockBuilder('Composer\Installer\InstallationManager')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->installationManager->expects(static::any())
            ->method('isPackageInstalled')
            ->willReturn(true)
        ;

        $this->composer->expects(static::any())
            ->method('getPackage')
            ->willReturn($this->package)
        ;
    }

    protected function tearDown()
    {
        $this->package = null;
        $this->installedRepository = null;
        $this->assetType = null;
        $this->filter = null;
    }

    public function getDataProvider()
    {
        $configSkipPattern = array('pattern-skip-version' => false);
        $configSkipPatternPath = array('pattern-skip-version' => '(-patch)');

        return array(
            array('acme/foobar', 'v1.0.0',        'stable', array(),                                    false),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0'),            false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0'),            true,  $configSkipPatternPath),

            array('acme/foobar', 'v1.0.0',        'RC',     array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-RC1',    'RC',     array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-beta1',  'RC',     array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-alpha1', 'RC',     array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0'),            false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0'),            true,  $configSkipPatternPath),

            array('acme/foobar', 'v1.0.0',        'beta',   array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-RC1',    'beta',   array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-beta1',  'beta',   array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-alpha1', 'beta',   array('acme/foobar' => '>=1.0'),            true),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0'),            false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0'),            true,  $configSkipPatternPath),

            array('acme/foobar', 'v1.0.0',        'alpha',  array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-RC1',    'alpha',  array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-beta1',  'alpha',  array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-alpha1', 'alpha',  array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0'),            false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0'),            true,  $configSkipPatternPath),

            array('acme/foobar', 'v1.0.0',        'dev',    array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-RC1',    'dev',    array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-beta1',  'dev',    array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-alpha1', 'dev',    array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-patch1', 'dev',    array('acme/foobar' => '>=1.0'),            false),
            array('acme/foobar', 'v1.0.0-patch1', 'dev',    array('acme/foobar' => '>=1.0'),            false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'dev',    array('acme/foobar' => '>=1.0'),            true,  $configSkipPatternPath),

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

            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@stable'),      false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@stable'),      false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@stable'),      true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@RC'),          false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@RC'),          false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@RC'),          true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@beta'),        false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@beta'),        false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@beta'),        true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@alpha'),       false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@alpha'),       false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@alpha'),       true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@dev'),         false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@dev'),         false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0@dev'),         true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@stable'),      false),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@stable'),      false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@stable'),      true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@RC'),          false),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@RC'),          false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@RC'),          true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@beta'),        false),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@beta'),        false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@beta'),        true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@alpha'),       false),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@alpha'),       false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@alpha'),       true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@dev'),         false),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@dev'),         false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '>=1.0@dev'),         true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@stable'),      false),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@stable'),      false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@stable'),      true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@RC'),          false),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@RC'),          false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@RC'),          true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@beta'),        false),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@beta'),        false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@beta'),        true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@alpha'),       false),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@alpha'),       false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@alpha'),       true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@dev'),         false),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@dev'),         false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '>=1.0@dev'),         true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@stable'),      false),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@stable'),      false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@stable'),      true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@RC'),          false),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@RC'),          false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@RC'),          true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@beta'),        false),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@beta'),        false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@beta'),        true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@alpha'),       false),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@alpha'),       false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@alpha'),       true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@dev'),         false),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@dev'),         false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '>=1.0@dev'),         true,  $configSkipPatternPath),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '~1.0'),              false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '~1.0'),              true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '~1.0'),              true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '~1.0'),              true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '~1.0'),              false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '~1.0'),              false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '~1.0'),              true,  $configSkipPatternPath),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0'),               false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0'),               true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0'),               true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0'),               true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0'),               false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0'),               false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0'),               true,  $configSkipPatternPath),

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

            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@stable'),           false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@stable'),           false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@stable'),           true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@RC'),               false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@RC'),               false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@RC'),               true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@beta'),             false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@beta'),             false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@beta'),             true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@alpha'),            false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@alpha'),            false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@alpha'),            true,  $configSkipPatternPath),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@dev'),              false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@dev'),              false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '@dev'),              true,  $configSkipPatternPath),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0'),               false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0-RC1'),           true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0-beta1'),         true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0-alpha1'),        true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1'),        false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1'),        false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1'),        true,  $configSkipPatternPath),

            array('acme/foobar', 'v1.0.0',        'RC',     array('acme/foobar' => '1.0'),               false),
            array('acme/foobar', 'v1.0.0-RC1',    'RC',     array('acme/foobar' => '1.0-RC1'),           false),
            array('acme/foobar', 'v1.0.0-beta1',  'RC',     array('acme/foobar' => '1.0-beta1'),         true),
            array('acme/foobar', 'v1.0.0-alpha1', 'RC',     array('acme/foobar' => '1.0-alpha1'),        true),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '1.0-patch1'),        false),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '1.0-patch1'),        false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'RC',     array('acme/foobar' => '1.0-patch1'),        true,  $configSkipPatternPath),

            array('acme/foobar', 'v1.0.0',        'beta',   array('acme/foobar' => '1.0'),               false),
            array('acme/foobar', 'v1.0.0-RC1',    'beta',   array('acme/foobar' => '1.0-RC1'),           false),
            array('acme/foobar', 'v1.0.0-beta1',  'beta',   array('acme/foobar' => '1.0-beta1'),         false),
            array('acme/foobar', 'v1.0.0-alpha1', 'beta',   array('acme/foobar' => '1.0-alpha1'),        true),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '1.0-patch1'),        false),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '1.0-patch1'),        false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'beta',   array('acme/foobar' => '1.0-patch1'),        true,  $configSkipPatternPath),

            array('acme/foobar', 'v1.0.0',        'alpha',  array('acme/foobar' => '1.0'),               false),
            array('acme/foobar', 'v1.0.0-RC1',    'alpha',  array('acme/foobar' => '1.0-RC1'),           false),
            array('acme/foobar', 'v1.0.0-beta1',  'alpha',  array('acme/foobar' => '1.0-beta1'),         false),
            array('acme/foobar', 'v1.0.0-alpha1', 'alpha',  array('acme/foobar' => '1.0-alpha1'),        false),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '1.0-patch1'),        false),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '1.0-patch1'),        false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'alpha',  array('acme/foobar' => '1.0-patch1'),        true,  $configSkipPatternPath),

            array('acme/foobar', 'v1.0.0',        'dev',    array('acme/foobar' => '1.0'),               false),
            array('acme/foobar', 'v1.0.0-RC1',    'dev',    array('acme/foobar' => '1.0-RC1'),           false),
            array('acme/foobar', 'v1.0.0-beta1',  'dev',    array('acme/foobar' => '1.0-beta1'),         false),
            array('acme/foobar', 'v1.0.0-alpha1', 'dev',    array('acme/foobar' => '1.0-alpha1'),        false),
            array('acme/foobar', 'v1.0.0-patch1', 'dev',    array('acme/foobar' => '1.0-patch1'),        false),
            array('acme/foobar', 'v1.0.0-patch1', 'dev',    array('acme/foobar' => '1.0-patch1'),        false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'dev',    array('acme/foobar' => '1.0-patch1'),        true,  $configSkipPatternPath),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0@stable'),        false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0-RC1@stable'),    true),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0-beta1@stable'),  true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0-alpha1@stable'), true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@stable'), false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@stable'), false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@stable'), true,  $configSkipPatternPath),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0@RC'),            false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0-RC1@RC'),        false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0-beta1@RC'),      true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0-alpha1@RC'),     true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@RC'),     false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@RC'),     false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@RC'),     true,  $configSkipPatternPath),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0@beta'),          false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0-RC1@beta'),      false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0-beta1@beta'),    false),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0-alpha1@beta'),   true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@beta'),   false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@beta'),   false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@beta'),   true,  $configSkipPatternPath),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0@alpha'),         false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0-RC1@alpha'),     false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0-beta1@alpha'),   false),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0-alpha1@alpha'),  false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@alpha'),  false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@alpha'),  false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@alpha'),  true,  $configSkipPatternPath),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0@dev'),           false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0-RC1@dev'),       false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0-beta1@dev'),     false),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0-alpha1@dev'),    false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@dev'),    false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@dev'),    false,  $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0-patch1@dev'),    true,   $configSkipPatternPath),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0@dev | 1.0.*@RC'), false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0@dev | 1.0.*@RC'), false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0@dev | 1.0.*@RC'), false),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0@dev | 1.0.*@RC'), false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0@dev | 1.0.*@RC'), false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0@dev | 1.0.*@RC'), false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0@dev | 1.0.*@RC'), true,  $configSkipPatternPath),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0 | 1.0.*@RC'),    false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0 | 1.0.*@RC'),    false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0 | 1.0.*@RC'),    true),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0 | 1.0.*@RC'),    true),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0 | 1.0.*@RC'),    false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0 | 1.0.*@RC'),    false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0 | 1.0.*@RC'),    true,  $configSkipPatternPath),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '1.0@dev | 1.0.*'),   false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '1.0@dev|1.0.*@RC'),  false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '1.0@dev | 1.0.*'),   false),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '1.0@dev | 1.0.*'),   false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0@dev | 1.0.*'),   false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0@dev | 1.0.*'),   false, $configSkipPattern),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '1.0@dev | 1.0.*'),   true,  $configSkipPatternPath),

            array('acme/foobar', 'standard/1.0.0', 'stable', array('acme/foobar' => '>=1.0'),            true),
        );
    }

    /**
     * @dataProvider getDataProvider
     *
     * @param string $packageName
     * @param string $version
     * @param string $minimumStability
     * @param array  $rootRequires
     * @param bool   $validSkip
     * @param array  $rootConfig
     */
    public function testSkipVersion($packageName, $version, $minimumStability, array $rootRequires, $validSkip, array $rootConfig = array())
    {
        $this->init($rootRequires, $minimumStability, $rootConfig);

        static::assertSame($validSkip, $this->filter->skip($this->assetType, $packageName, $version));
    }

    public function getDataProviderForDisableTest()
    {
        return array(
            array('acme/foobar', 'v1.0.0',        'stable', array(),                         false),

            array('acme/foobar', 'v1.0.0',        'stable', array('acme/foobar' => '>=1.0'), false),
            array('acme/foobar', 'v1.0.0-RC1',    'stable', array('acme/foobar' => '>=1.0'), false),
            array('acme/foobar', 'v1.0.0-beta1',  'stable', array('acme/foobar' => '>=1.0'), false),
            array('acme/foobar', 'v1.0.0-alpha1', 'stable', array('acme/foobar' => '>=1.0'), false),
            array('acme/foobar', 'v1.0.0-patch1', 'stable', array('acme/foobar' => '>=1.0'), false),
        );
    }

    /**
     * @dataProvider getDataProviderForDisableTest
     *
     * @param $packageName
     * @param $version
     * @param $minimumStability
     * @param array $rootRequires
     * @param $validSkip
     */
    public function testDisabledFilterWithInstalledPackage($packageName, $version, $minimumStability, array $rootRequires, $validSkip)
    {
        $this->init($rootRequires, $minimumStability);
        $this->filter->setEnabled(false);

        static::assertSame($validSkip, $this->filter->skip($this->assetType, $packageName, $version));
    }

    public function getDataForInstalledTests()
    {
        $optn = 'optimize-with-installed-packages';
        $optn2 = 'optimize-with-conjunctive';

        $opt1 = array();
        $opt2 = array($optn => true, $optn2 => true);
        $opt3 = array($optn => false, $optn2 => true);
        $opt4 = array($optn => true, $optn2 => false);

        return array(
            array($opt1, 'acme/foobar', 'v1.0.0', 'stable', '>=0.9', '1.0.0', true),
            array($opt2, 'acme/foobar', 'v1.0.0', 'stable', '>=0.9', '1.0.0', true),
            array($opt3, 'acme/foobar', 'v1.0.0', 'stable', '>=0.9', '1.0.0', false),
            array($opt4, 'acme/foobar', 'v1.0.0', 'stable', '>=0.9', '1.0.0', false),
            array($opt1, 'acme/foobar', 'v0.9.0', 'stable', '>=0.9', '1.0.0', true),
            array($opt2, 'acme/foobar', 'v0.9.0', 'stable', '>=0.9', '1.0.0', true),
            array($opt3, 'acme/foobar', 'v0.9.0', 'stable', '>=0.9', '1.0.0', false),
            array($opt4, 'acme/foobar', 'v0.9.0', 'stable', '>=0.9', '1.0.0', false),

            array($opt1, 'acme/foobar', 'v1.0.0', 'stable', '>=0.9', null,    false),
            array($opt2, 'acme/foobar', 'v1.0.0', 'stable', '>=0.9', null,    false),
            array($opt3, 'acme/foobar', 'v1.0.0', 'stable', '>=0.9', null,    false),
            array($opt4, 'acme/foobar', 'v1.0.0', 'stable', '>=0.9', null,    false),
            array($opt1, 'acme/foobar', 'v0.9.0', 'stable', '>=0.9', null,    false),
            array($opt2, 'acme/foobar', 'v0.9.0', 'stable', '>=0.9', null,    false),
            array($opt3, 'acme/foobar', 'v0.9.0', 'stable', '>=0.9', null,    false),
            array($opt4, 'acme/foobar', 'v0.9.0', 'stable', '>=0.9', null,    false),

            array($opt1, 'acme/foobar', 'v1.0.0', 'stable', null,    '1.0.0', true),
            array($opt2, 'acme/foobar', 'v1.0.0', 'stable', null,    '1.0.0', true),
            array($opt3, 'acme/foobar', 'v1.0.0', 'stable', null,    '1.0.0', false),
            array($opt4, 'acme/foobar', 'v1.0.0', 'stable', null,    '1.0.0', true),
            array($opt1, 'acme/foobar', 'v0.9.0', 'stable', null,    '1.0.0', true),
            array($opt2, 'acme/foobar', 'v0.9.0', 'stable', null,    '1.0.0', true),
            array($opt3, 'acme/foobar', 'v0.9.0', 'stable', null,    '1.0.0', false),
            array($opt4, 'acme/foobar', 'v0.9.0', 'stable', null,    '1.0.0', true),

            array($opt1, 'acme/foobar', 'v1.0.0', 'stable', null,    null,    false),
            array($opt2, 'acme/foobar', 'v1.0.0', 'stable', null,    null,    false),
            array($opt3, 'acme/foobar', 'v1.0.0', 'stable', null,    null,    false),
            array($opt4, 'acme/foobar', 'v1.0.0', 'stable', null,    null,    false),
            array($opt1, 'acme/foobar', 'v0.9.0', 'stable', null,    null,    false),
            array($opt2, 'acme/foobar', 'v0.9.0', 'stable', null,    null,    false),
            array($opt3, 'acme/foobar', 'v0.9.0', 'stable', null,    null,    false),
            array($opt4, 'acme/foobar', 'v0.9.0', 'stable', null,    null,    false),

            array($opt1, 'acme/foobar', 'v1.0.0', 'dev',   '>=0.9@stable', '1.0.0', true),
            array($opt2, 'acme/foobar', 'v1.0.0', 'dev',   '>=0.9@stable', '1.0.0', true),
            array($opt3, 'acme/foobar', 'v1.0.0', 'dev',   '>=0.9@stable', '1.0.0', false),
            array($opt4, 'acme/foobar', 'v1.0.0', 'dev',   '>=0.9@stable', '1.0.0', false),
            array($opt1, 'acme/foobar', 'v0.9.0', 'dev',   '>=0.9@stable', '1.0.0', true),
            array($opt2, 'acme/foobar', 'v0.9.0', 'dev',   '>=0.9@stable', '1.0.0', true),
            array($opt3, 'acme/foobar', 'v0.9.0', 'dev',   '>=0.9@stable', '1.0.0', false),
            array($opt4, 'acme/foobar', 'v0.9.0', 'dev',   '>=0.9@stable', '1.0.0', false),

            array($opt1, 'acme/foobar', 'v1.0.0', 'dev',   '>=0.9@stable', null,    false),
            array($opt2, 'acme/foobar', 'v1.0.0', 'dev',   '>=0.9@stable', null,    false),
            array($opt3, 'acme/foobar', 'v1.0.0', 'dev',   '>=0.9@stable', null,    false),
            array($opt4, 'acme/foobar', 'v1.0.0', 'dev',   '>=0.9@stable', null,    false),
            array($opt1, 'acme/foobar', 'v0.9.0', 'dev',   '>=0.9@stable', null,    false),
            array($opt2, 'acme/foobar', 'v0.9.0', 'dev',   '>=0.9@stable', null,    false),
            array($opt3, 'acme/foobar', 'v0.9.0', 'dev',   '>=0.9@stable', null,    false),
            array($opt4, 'acme/foobar', 'v0.9.0', 'dev',   '>=0.9@stable', null,    false),
        );
    }

    /**
     * @dataProvider getDataForInstalledTests
     *
     * @param array       $config
     * @param string      $packageName
     * @param string      $version
     * @param string      $minimumStability
     * @param null|string $rootRequireVersion
     * @param null|string $installedVersion
     * @param bool        $validSkip
     */
    public function testFilterWithInstalledPackage(array $config, $packageName, $version, $minimumStability, $rootRequireVersion, $installedVersion, $validSkip)
    {
        $installed = null === $installedVersion
            ? array()
            : array($packageName => $installedVersion);

        $require = null === $rootRequireVersion
            ? array()
            : array($packageName => $rootRequireVersion);

        $this->installedRepository = $this->getMockBuilder('Composer\Repository\InstalledFilesystemRepository')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->installedRepository->expects(static::any())
            ->method('getPackages')
            ->willReturn($this->convertInstalled($installed))
        ;

        $this->init($require, $minimumStability, $config);

        static::assertSame($validSkip, $this->filter->skip($this->assetType, $packageName, $version));
    }

    /**
     * Init test.
     *
     * @param array  $requires
     * @param string $minimumStability
     * @param array  $config
     */
    protected function init(array $requires = array(), $minimumStability = 'stable', array $config = array())
    {
        $parser = new ArrayLoader();
        $linkRequires = $parser->parseLinks('__ROOT__', '1.0.0', 'requires', $requires);

        $stabilityFlags = $this->findStabilityFlags($requires);

        $this->package->expects(static::any())
            ->method('getRequires')
            ->willReturn($linkRequires)
        ;
        $this->package->expects(static::any())
            ->method('getDevRequires')
            ->willReturn(array())
        ;
        $this->package->expects(static::any())
            ->method('getMinimumStability')
            ->willReturn($minimumStability)
        ;
        $this->package->expects(static::any())
            ->method('getStabilityFlags')
            ->willReturn($stabilityFlags)
        ;
        $this->package->expects(static::any())
            ->method('getConfig')
            ->willReturn(array(
                'fxp-asset' => $config,
            ))
        ;

        /** @var RootPackageInterface $package */
        $package = $this->package;
        $config = ConfigBuilder::build($this->composer);

        $this->filter = new VcsPackageFilter($config, $package, $this->installationManager, $this->installedRepository);
    }

    /**
     * Convert the installed package data tests to mock package instance.
     *
     * @param array $installed The config of installed packages
     *
     * @return array The package instance of installed packages
     */
    protected function convertInstalled(array $installed)
    {
        $packages = array();
        $parser = new VersionParser();

        foreach ($installed as $name => $version) {
            $package = $this->getMockBuilder('Composer\Package\PackageInterface')->getMock();

            $package->expects(static::any())
                ->method('getName')
                ->willReturn($name)
            ;

            $package->expects(static::any())
                ->method('getVersion')
                ->willReturn($parser->normalize($version))
            ;

            $package->expects(static::any())
                ->method('getPrettyVersion')
                ->willReturn($version)
            ;

            $packages[] = $package;
        }

        return $packages;
    }

    /**
     * Find the stability flag of requires.
     *
     * @param array $requires The require dependencies
     *
     * @return array
     */
    protected function findStabilityFlags(array $requires)
    {
        $flags = array();
        $stabilities = Package::$stabilities;

        foreach ($requires as $require => $prettyConstraint) {
            if (preg_match_all('/@('.implode('|', array_keys($stabilities)).')/', $prettyConstraint, $matches)) {
                $flags[$require] = $stabilities[$matches[1][0]];
            }
        }

        return $flags;
    }
}
