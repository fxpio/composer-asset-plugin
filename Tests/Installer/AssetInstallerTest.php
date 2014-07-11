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

    /**
     * @var AssetTypeInterface
     */
    protected $type;

    protected function setUp()
    {
        $io = $this->getMock('Composer\IO\IOInterface');
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

        $composer = $this->getMock('Composer\Composer');
        $composer->expects($this->any())
            ->method('getPackage')
            ->will($this->returnValue($this->package));
        $composer->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($config));

        $type = $this->getMock('Fxp\Composer\AssetPlugin\Type\AssetTypeInterface');
        $type->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('foo'));
        $type->expects($this->any())
            ->method('getComposerVendorName')
            ->will($this->returnValue('foo-asset'));
        $type->expects($this->any())
            ->method('getComposerType')
            ->will($this->returnValue('foo-asset-library'));
        $type->expects($this->any())
            ->method('getFilename')
            ->will($this->returnValue('foo.json'));
        $type->expects($this->any())
            ->method('getVersionConverter')
            ->will($this->returnValue($this->getMock('Fxp\Composer\AssetPlugin\Converter\VersionConverterInterface')));
        $type->expects($this->any())
            ->method('getPackageConverter')
            ->will($this->returnValue($this->getMock('Fxp\Composer\AssetPlugin\Converter\PackageConverterInterface')));

        $this->composer = $composer;
        $this->io = $io;
        $this->type = $type;
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

        $installerPath = $installer->getInstallPath($this->createPackageMock('foo-asset/foo', '1.0.0'));
        $installerPath = str_replace('\\', '/', $installerPath);
        $this->assertEquals($vendorDir.'/foo', $installerPath);

        $installerPath2 = $installer->getInstallPath($this->createPackageMock('foo-asset/foo/bar', '1.0.0'));
        $installerPath2 = str_replace('\\', '/', $installerPath2);
        $this->assertEquals($vendorDir.'/foo/bar', $installerPath2);
    }

    public function testCustomFooDir()
    {
        $vendorDir = realpath(sys_get_temp_dir()) . '/composer-test/web';
        $vendorDir = str_replace('\\', '/', $vendorDir);

        /* @var \PHPUnit_Framework_MockObject_MockObject $package */
        $package = $this->package;
        $package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array(
                'asset-installer-paths' => array(
                    $this->type->getComposerType() => $vendorDir,
                )
            )));

        $installer = $this->createInstaller();

        $installerPath = $installer->getInstallPath($this->createPackageMock('foo-asset/foo', '1.0.0'));
        $installerPath = str_replace('\\', '/', $installerPath);
        $this->assertEquals($vendorDir.'/foo', $installerPath);

        $installerPath2 = $installer->getInstallPath($this->createPackageMock('foo-asset/foo/bar', '1.0.0'));
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
