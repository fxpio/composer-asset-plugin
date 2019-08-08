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
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Filesystem;
use Fxp\Composer\AssetPlugin\Config\ConfigBuilder;
use Fxp\Composer\AssetPlugin\Installer\AssetInstaller;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Tests of asset installer.
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class AssetInstallerTest extends \PHPUnit\Framework\TestCase
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
     * @var \PHPUnit_Framework_MockObject_MockObject|RootPackageInterface
     */
    protected $package;

    /**
     * @var AssetTypeInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $type;

    protected function setUp()
    {
        $this->io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $config = $this->getMockBuilder('Composer\Config')->getMock();
        $config->expects(static::any())
            ->method('get')
            ->willReturnCallback(function ($key) {
                $value = null;

                switch ($key) {
                    case 'cache-repo-dir':
                        $value = sys_get_temp_dir().'/composer-test-repo-cache';

                        break;
                    case 'vendor-dir':
                        $value = sys_get_temp_dir().'/composer-test/vendor';

                        break;
                }

                return $value;
            })
        ;

        $this->package = $this->getMockBuilder('Composer\Package\RootPackageInterface')->getMock();

        $this->composer = $this->getMockBuilder('Composer\Composer')->getMock();
        $this->composer->expects(static::any())
            ->method('getPackage')
            ->willReturn($this->package)
        ;
        $this->composer->expects(static::any())
            ->method('getConfig')
            ->willReturn($config)
        ;

        $this->type = $this->getMockBuilder('Fxp\Composer\AssetPlugin\Type\AssetTypeInterface')->getMock();
        $this->type->expects(static::any())
            ->method('getName')
            ->willReturn('foo')
        ;
        $this->type->expects(static::any())
            ->method('getComposerVendorName')
            ->willReturn('foo-asset')
        ;
        $this->type->expects(static::any())
            ->method('getComposerType')
            ->willReturn('foo-asset-library')
        ;
        $this->type->expects(static::any())
            ->method('getFilename')
            ->willReturn('foo.json')
        ;
        $this->type->expects(static::any())
            ->method('getVersionConverter')
            ->willReturn($this->getMockBuilder('Fxp\Composer\AssetPlugin\Converter\VersionConverterInterface')->getMock())
        ;
        $this->type->expects(static::any())
            ->method('getPackageConverter')
            ->willReturn($this->getMockBuilder('Fxp\Composer\AssetPlugin\Converter\PackageConverterInterface')->getMock())
        ;
    }

    protected function tearDown()
    {
        $this->package = null;
        $this->composer = null;
        $this->io = null;

        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/composer-test-repo-cache');
        $fs->remove(sys_get_temp_dir().'/composer-test');
    }

    public function testDefaultVendorDir()
    {
        $installer = $this->createInstaller();
        $vendorDir = realpath(sys_get_temp_dir()).'/composer-test/vendor/'.$this->type->getComposerVendorName();
        $vendorDir = str_replace('\\', '/', $vendorDir);

        $installerPath = $installer->getInstallPath($this->createPackageMock('foo-asset/foo'));
        $installerPath = str_replace('\\', '/', $installerPath);
        static::assertEquals($vendorDir.'/foo', $installerPath);

        $installerPath2 = $installer->getInstallPath($this->createPackageMock('foo-asset/foo/bar'));
        $installerPath2 = str_replace('\\', '/', $installerPath2);
        static::assertEquals($vendorDir.'/foo/bar', $installerPath2);
    }

    public function testCustomFooDir()
    {
        $vendorDir = realpath(sys_get_temp_dir()).'/composer-test/web';
        $vendorDir = str_replace('\\', '/', $vendorDir);

        $package = $this->package;
        $package->expects(static::any())
            ->method('getExtra')
            ->willReturn(array(
                'asset-installer-paths' => array(
                    $this->type->getComposerType() => $vendorDir,
                ),
            ))
        ;

        $installer = $this->createInstaller();

        $installerPath = $installer->getInstallPath($this->createPackageMock('foo-asset/foo'));
        $installerPath = str_replace('\\', '/', $installerPath);
        static::assertEquals($vendorDir.'/foo', $installerPath);

        $installerPath2 = $installer->getInstallPath($this->createPackageMock('foo-asset/foo/bar'));
        $installerPath2 = str_replace('\\', '/', $installerPath2);
        static::assertEquals($vendorDir.'/foo/bar', $installerPath2);
    }

    public function testInstall()
    {
        /** @var RootPackageInterface $rootPackage */
        $rootPackage = $this->createRootPackageMock();
        /** @var IOInterface $io */
        $io = $this->io;
        /** @var AssetTypeInterface $type */
        $type = $this->type;
        $vendorDir = realpath(sys_get_temp_dir()).\DIRECTORY_SEPARATOR.'composer-test'.\DIRECTORY_SEPARATOR.'vendor';

        $this->composer->setPackage($rootPackage);

        $dm = $this->getMockBuilder('Composer\Downloader\DownloadManager')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->composer->expects(static::any())
            ->method('getDownloadManager')
            ->willReturn($dm)
        ;

        /** @var \PHPUnit_Framework_MockObject_MockObject $package */
        $package = $this->createPackageMock('foo-asset/package');

        /** @var PackageInterface $package */
        $packageDir = $vendorDir.'/'.$package->getPrettyName();

        $dm->expects(static::once())
            ->method('download')
            ->with($package, $vendorDir.\DIRECTORY_SEPARATOR.'foo-asset/package')
        ;

        $repository = $this->getMockBuilder('Composer\Repository\InstalledRepositoryInterface')->getMock();
        $repository->expects(static::once())
            ->method('addPackage')
            ->with($package)
        ;

        $config = ConfigBuilder::build($this->composer);
        $library = new AssetInstaller($config, $io, $this->composer, $type);

        /* @var InstalledRepositoryInterface $repository */
        $library->install($repository, $package);
        static::assertFileExists($vendorDir, 'Vendor dir should be created');

        $this->ensureDirectoryExistsAndClear($packageDir);
    }

    /**
     * Creates the asset installer.
     *
     * @return AssetInstaller
     */
    protected function createInstaller()
    {
        /** @var IOInterface $io */
        $io = $this->io;
        /** @var Composer $composer */
        $composer = $this->composer;
        /** @var AssetTypeInterface $type */
        $type = $this->type;
        $config = ConfigBuilder::build($composer);

        return new AssetInstaller($config, $io, $composer, $type);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RootPackageInterface
     */
    protected function createRootPackageMock()
    {
        $package = $this->getMockBuilder('Composer\Package\RootPackageInterface')
            ->getMock()
        ;

        $package->expects(static::any())
            ->method('getConfig')
            ->willReturn(array())
        ;

        return $package;
    }

    protected function ensureDirectoryExistsAndClear($directory)
    {
        $fs = new Filesystem();
        if (is_dir($directory)) {
            $fs->removeDirectory($directory);
        }
        mkdir($directory, 0777, true);
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
        return $this->getMockBuilder('Composer\Package\Package')
            ->setConstructorArgs(array($name, '1.0.0.0', '1.0.0'))
            ->enableProxyingToOriginalMethods()
            ->getMock()
        ;
    }
}
