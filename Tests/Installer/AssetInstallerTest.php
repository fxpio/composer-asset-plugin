<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Installer;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use Fxp\Composer\AssetPlugin\Installer\AssetInstaller;
use Fxp\Composer\AssetPlugin\Type\BowerAssetType;

/**
 * Tests of asset installer.
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
class AssetInstallerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var PackageInterface
     */
    protected $package;

    protected function setUp()
    {
        $io = $this->getMock('Composer\IO\IOInterface');
        $config = $this->getMock('Composer\Config');
        $config->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function($key) {
                switch ($key) {
                    case 'cache-repo-dir':
                        return sys_get_temp_dir() . '/composer-test-repo-cache';
                    case 'vendor-dir':
                        return sys_get_temp_dir() . '/composer-test/vendor';
                }
            }));

        $this->package = $this->getMock('Composer\Package\PackageInterface');

        $composer = $this->getMock('Composer\Composer');
        $composer->expects($this->any())
            ->method('getPackage')
            ->will($this->returnValue($this->package));
        $composer->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($config));

        $this->composer = $composer;
        $this->io = $io;
    }

    protected function tearDown()
    {
        $this->package = null;
        $this->composer = null;
        $this->io = null;

        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir() . '/composer-test-repo-cache');
        $fs->remove(sys_get_temp_dir() . '/composer-test/vendor');
    }

    public function testDefaultVendorDir()
    {
        $type = new BowerAssetType();
        $installer = new AssetInstaller($this->io, $this->composer, $type);
        $vendorDir = realpath(sys_get_temp_dir()) . '/composer-test/vendor/'.$type->getComposerVendorName();
        $vendorDir = str_replace('\\', '/', $vendorDir);

        $installerPath = $installer->getInstallPath($this->createPackageMock('bower-asset/foo', '1.0.0'));
        $installerPath = str_replace('\\', '/', $installerPath);
        $this->assertEquals($vendorDir.'/foo', $installerPath);

        $installerPath2 = $installer->getInstallPath($this->createPackageMock('bower-asset/foo/bar', '1.0.0'));
        $installerPath2 = str_replace('\\', '/', $installerPath2);
        $this->assertEquals($vendorDir.'/foo/bar', $installerPath2);
    }

    public function testCustomBowerDir()
    {
        $type = new BowerAssetType();
        $vendorDir = realpath(sys_get_temp_dir()) . '/composer-test/web';
        $vendorDir = str_replace('\\', '/', $vendorDir);

        $this->package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array(
                'asset-installer-paths' => array(
                    $type->getComposerType() => $vendorDir,
                )
            )));

        $installer = new AssetInstaller($this->io, $this->composer, $type);

        $installerPath = $installer->getInstallPath($this->createPackageMock('bower-asset/foo', '1.0.0'));
        $installerPath = str_replace('\\', '/', $installerPath);
        $this->assertEquals($vendorDir.'/foo', $installerPath);

        $installerPath2 = $installer->getInstallPath($this->createPackageMock('bower-asset/foo/bar', '1.0.0'));
        $installerPath2 = str_replace('\\', '/', $installerPath2);
        $this->assertEquals($vendorDir.'/foo/bar', $installerPath2);
    }

    private function createPackageMock($name, $version)
    {
        return $this->getMock('Composer\Package\Package', null, array($name, '1.0.0.0', '1.0.0'));
    }
}
