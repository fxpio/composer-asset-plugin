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
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Tests of asset installer.
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
class AssetInstallerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Composer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $composer;

    /**
     * @var IOInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $io;

    /**
     * @var PackageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $package;

    /**
     * @var AssetTypeInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $type;

    protected function setUp()
    {
        $this->io = $this->getMock('Composer\IO\IOInterface');
        $config = $this->getMock('Composer\Config');
        $config->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($key) {
                switch ($key) {
                    case 'cache-repo-dir':
                        return sys_get_temp_dir() . '/composer-test-repo-cache';
                    case 'vendor-dir':
                        return sys_get_temp_dir() . '/composer-test/vendor';
                }

                return null;
            }));

        $this->package = $this->getMock('Composer\Package\PackageInterface');

        $this->composer = $this->getMock('Composer\Composer');
        $this->composer->expects($this->any())
            ->method('getPackage')
            ->will($this->returnValue($this->package));
        $this->composer->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($config));

        $this->type = $this->getMock('Fxp\Composer\AssetPlugin\Type\AssetTypeInterface');
        $this->type->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('foo'));
        $this->type->expects($this->any())
            ->method('getComposerVendorName')
            ->will($this->returnValue('foo-asset'));
        $this->type->expects($this->any())
            ->method('getComposerType')
            ->will($this->returnValue('foo-asset-library'));
        $this->type->expects($this->any())
            ->method('getFilename')
            ->will($this->returnValue('foo.json'));
        $this->type->expects($this->any())
            ->method('getVersionConverter')
            ->will($this->returnValue($this->getMock('Fxp\Composer\AssetPlugin\Converter\VersionConverterInterface')));
        $this->type->expects($this->any())
            ->method('getPackageConverter')
            ->will($this->returnValue($this->getMock('Fxp\Composer\AssetPlugin\Converter\PackageConverterInterface')));
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
        $installer = $this->createInstaller();
        $vendorDir = realpath(sys_get_temp_dir()) . '/composer-test/vendor/'.$this->type->getComposerVendorName();
        $vendorDir = str_replace('\\', '/', $vendorDir);

        $installerPath = $installer->getInstallPath($this->createPackageMock('foo-asset/foo'));
        $installerPath = str_replace('\\', '/', $installerPath);
        $this->assertEquals($vendorDir.'/foo', $installerPath);

        $installerPath2 = $installer->getInstallPath($this->createPackageMock('foo-asset/foo/bar'));
        $installerPath2 = str_replace('\\', '/', $installerPath2);
        $this->assertEquals($vendorDir.'/foo/bar', $installerPath2);
    }

    public function testCustomFooDir()
    {
        $vendorDir = realpath(sys_get_temp_dir()) . '/composer-test/web';
        $vendorDir = str_replace('\\', '/', $vendorDir);

        $this->package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array(
                'asset-installer-paths' => array(
                    $this->type->getComposerType() => $vendorDir,
                )
            )));

        $installer = $this->createInstaller();

        $installerPath = $installer->getInstallPath($this->createPackageMock('foo-asset/foo'));
        $installerPath = str_replace('\\', '/', $installerPath);
        $this->assertEquals($vendorDir.'/foo', $installerPath);

        $installerPath2 = $installer->getInstallPath($this->createPackageMock('foo-asset/foo/bar'));
        $installerPath2 = str_replace('\\', '/', $installerPath2);
        $this->assertEquals($vendorDir.'/foo/bar', $installerPath2);
    }

    /**
     * Creates the asset installer.
     *
     * @return AssetInstaller
     */
    protected function createInstaller()
    {
        return new AssetInstaller($this->io, $this->composer, $this->type);
    }

    /**
     * Creates the mock package.
     *
     * @param string $name
     *
     * @return PackageInterface
     */
    private function createPackageMock($name)
    {
        return $this->getMock('Composer\Package\Package', null, array($name, '1.0.0.0', '1.0.0'));
    }
}
