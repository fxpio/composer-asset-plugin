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

use Composer\Downloader\DownloadManager;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Filesystem;
use Composer\TestCase;
use Composer\Composer;
use Composer\Config;
use Fxp\Composer\AssetPlugin\Installer\BowerInstaller;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Tests of bower asset installer.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class BowerInstallerTest extends TestCase
{
    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $vendorDir;

    /**
     * @var string
     */
    protected $binDir;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DownloadManager
     */
    protected $dm;

    /**
     * @var InstalledRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var IOInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $io;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var AssetTypeInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $type;

    protected function setUp()
    {
        $this->fs = new Filesystem();

        $this->composer = new Composer();
        $this->config = new Config();
        $this->composer->setConfig($this->config);

        $this->vendorDir = realpath(sys_get_temp_dir()).DIRECTORY_SEPARATOR.'composer-test-vendor';
        $this->ensureDirectoryExistsAndClear($this->vendorDir);

        $this->binDir = realpath(sys_get_temp_dir()).DIRECTORY_SEPARATOR.'composer-test-bin';
        $this->ensureDirectoryExistsAndClear($this->binDir);

        $this->config->merge(array(
                'config' => array(
                    'vendor-dir' => $this->vendorDir,
                    'bin-dir' => $this->binDir,
                ),
            ));

        $this->dm = $this->getMockBuilder('Composer\Downloader\DownloadManager')
            ->disableOriginalConstructor()
            ->getMock();
        /* @var DownloadManager $dm */
        $dm = $this->dm;
        $this->composer->setDownloadManager($dm);

        $this->repository = $this->getMock('Composer\Repository\InstalledRepositoryInterface');
        $this->io = $this->getMock('Composer\IO\IOInterface');

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
        $this->fs->removeDirectory($this->vendorDir);
        $this->fs->removeDirectory($this->binDir);
    }

    public function testInstallerCreationShouldNotCreateVendorDirectory()
    {
        $this->fs->removeDirectory($this->vendorDir);
        $this->composer->setPackage($this->createRootPackageMock());

        new BowerInstaller($this->io, $this->composer, $this->type);
        $this->assertFileNotExists($this->vendorDir);
    }

    public function testInstallerCreationShouldNotCreateBinDirectory()
    {
        $this->fs->removeDirectory($this->binDir);
        $this->composer->setPackage($this->createRootPackageMock());

        new BowerInstaller($this->io, $this->composer, $this->type);
        $this->assertFileNotExists($this->binDir);
    }

    public function testIsInstalled()
    {
        $this->composer->setPackage($this->createRootPackageMock());

        $library = new BowerInstaller($this->io, $this->composer, $this->type);
        $package = $this->createPackageMock();

        $package
            ->expects($this->any())
            ->method('getPrettyName')
            ->will($this->returnValue('foo-asset/package'));

        $packageDir = $this->vendorDir . '/' . $package->getPrettyName();
        mkdir($packageDir, 0777, true);

        $this->repository
            ->expects($this->exactly(2))
            ->method('hasPackage')
            ->with($package)
            ->will($this->onConsecutiveCalls(true, false));

        $this->assertTrue($library->isInstalled($this->repository, $package));
        $this->assertFalse($library->isInstalled($this->repository, $package));

        $this->ensureDirectoryExistsAndClear($packageDir);
    }

    public function getAssetIgnoreFiles()
    {
        return array(
            array(array()),
            array(array('foo', 'bar')),
        );
    }

    /**
     * @dataProvider getAssetIgnoreFiles
     */
    public function testInstall(array $ignoreFiles)
    {
        $this->composer->setPackage($this->createRootPackageMock());

        $library = new BowerInstaller($this->io, $this->composer, $this->type);
        $package = $this->createPackageMock($ignoreFiles);

        $package
            ->expects($this->any())
            ->method('getPrettyName')
            ->will($this->returnValue('foo-asset/package'));

        $packageDir = $this->vendorDir . '/' . $package->getPrettyName();
        mkdir($packageDir, 0777, true);

        $this->dm
            ->expects($this->once())
            ->method('download')
            ->with($package, $this->vendorDir.DIRECTORY_SEPARATOR.'foo-asset/package');

        $this->repository
            ->expects($this->once())
            ->method('addPackage')
            ->with($package);

        $library->install($this->repository, $package);
        $this->assertFileExists($this->vendorDir, 'Vendor dir should be created');
        $this->assertFileExists($this->binDir, 'Bin dir should be created');

        $this->ensureDirectoryExistsAndClear($packageDir);
    }

    public function testUninstall()
    {
        $this->composer->setPackage($this->createRootPackageMock());

        $library = new BowerInstaller($this->io, $this->composer, $this->type);
        $package = $this->createPackageMock();

        $package
            ->expects($this->any())
            ->method('getPrettyName')
            ->will($this->returnValue('foo-asset/pkg'));

        $this->repository
            ->expects($this->exactly(2))
            ->method('hasPackage')
            ->with($package)
            ->will($this->onConsecutiveCalls(true, false));

        $this->dm
            ->expects($this->once())
            ->method('remove')
            ->with($package, $this->vendorDir.DIRECTORY_SEPARATOR.'foo-asset/pkg');

        $this->repository
            ->expects($this->once())
            ->method('removePackage')
            ->with($package);

        $library->uninstall($this->repository, $package);

        $this->setExpectedException('InvalidArgumentException');

        $library->uninstall($this->repository, $package);
    }

    public function testGetInstallPath()
    {
        $this->composer->setPackage($this->createRootPackageMock());

        $library = new BowerInstaller($this->io, $this->composer, $this->type);
        $package = $this->createPackageMock();

        $package
            ->expects($this->once())
            ->method('getTargetDir')
            ->will($this->returnValue(null));
        $package
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('foo-asset/bar'));
        $package
            ->expects($this->any())
            ->method('getPrettyName')
            ->will($this->returnValue('foo-asset/bar'));

        $exceptDir = $this->vendorDir.'/'.$package->getName();
        $exceptDir = str_replace('\\', '/', $exceptDir);
        $packageDir = $library->getInstallPath($package);
        $packageDir = str_replace('\\', '/', $packageDir);

        $this->assertEquals($exceptDir, $packageDir);
    }

    public function testGetInstallPathWithTargetDir()
    {
        $this->composer->setPackage($this->createRootPackageMock());

        $library = new BowerInstaller($this->io, $this->composer, $this->type);
        $package = $this->createPackageMock();

        $package
            ->expects($this->once())
            ->method('getTargetDir')
            ->will($this->returnValue('Some/Namespace'));
        $package
            ->expects($this->any())
            ->method('getPrettyName')
            ->will($this->returnValue('foo-asset/bar'));

        $exceptDir = $this->vendorDir.'/'.$package->getPrettyName().'/Some/Namespace';
        $exceptDir = str_replace('\\', '/', $exceptDir);
        $packageDir = $library->getInstallPath($package);
        $packageDir = str_replace('\\', '/', $packageDir);

        $this->assertEquals($exceptDir, $packageDir);
    }

    /**
     * @param array $ignoreFiles
     *
     * @return PackageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createPackageMock(array $ignoreFiles = array())
    {
        $package = $this->getMockBuilder('Composer\Package\Package')
            ->setConstructorArgs(array(md5(mt_rand()), '1.0.0.0', '1.0.0'))
            ->getMock();

        $package
            ->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array(
                'bower-asset-ignore' => $ignoreFiles,
            )));

        return $package;
    }

    /**
     * @return RootPackageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createRootPackageMock()
    {
        $package = $this->getMockBuilder('Composer\Package\RootPackageInterface')
            ->setConstructorArgs(array(md5(mt_rand()), '1.0.0.0', '1.0.0'))
            ->getMock();

        $package
            ->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array()));

        return $package;
    }
}
