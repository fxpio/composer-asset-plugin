<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Repository\Vcs;

use Composer\Config;
use Composer\Util\Filesystem;
use Fxp\Composer\AssetPlugin\Repository\Vcs\PerforceDriver;
use Fxp\Composer\AssetPlugin\Tests\TestCase;
use Fxp\Composer\AssetPlugin\Util\Perforce;

/**
 * Tests of vcs perforce repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class PerforceDriverTest extends TestCase
{
    const TEST_URL = 'TEST_PERFORCE_URL';
    const TEST_DEPOT = 'TEST_DEPOT_CONFIG';
    const TEST_BRANCH = 'TEST_BRANCH_CONFIG';
    protected $config;
    protected $io;
    protected $process;
    protected $remoteFileSystem;
    protected $testPath;

    /**
     * @var PerforceDriver
     */
    protected $driver;

    protected $repoConfig;

    /**
     * @var Perforce|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $perforce;

    protected function setUp()
    {
        $this->testPath = $this->getUniqueTmpDirectory();
        $this->config = $this->getTestConfig($this->testPath);
        $this->repoConfig = $this->getTestRepoConfig();
        $this->io = $this->getMockIOInterface();
        $this->process = $this->getMockProcessExecutor();
        $this->remoteFileSystem = $this->getMockRemoteFilesystem();
        $this->perforce = $this->getMockPerforce();
        $this->driver = new PerforceDriver($this->repoConfig, $this->io, $this->config, $this->process, $this->remoteFileSystem);
        $this->overrideDriverInternalPerforce($this->perforce);
    }

    protected function tearDown()
    {
        //cleanup directory under test path
        $fs = new Filesystem();
        $fs->removeDirectory($this->testPath);
        $this->driver = null;
        $this->perforce = null;
        $this->remoteFileSystem = null;
        $this->process = null;
        $this->io = null;
        $this->repoConfig = null;
        $this->config = null;
        $this->testPath = null;
    }

    public function testInitializeCapturesVariablesFromRepoConfig()
    {
        $driver = new PerforceDriver($this->repoConfig, $this->io, $this->config, $this->process, $this->remoteFileSystem);
        $driver->initialize();
        static::assertEquals(self::TEST_URL, $driver->getUrl());
        static::assertEquals(self::TEST_DEPOT, $driver->getDepot());
        static::assertEquals(self::TEST_BRANCH, $driver->getBranch());
    }

    /**
     * Test that supports() simply return false.
     *
     * @covers \Composer\Repository\Vcs\PerforceDriver::supports
     */
    public function testSupportsReturnsFalseNoDeepCheck()
    {
        $this->expectOutputString('');
        static::assertFalse(PerforceDriver::supports($this->io, $this->config, 'existing.url'));
    }

    public function testInitializeLogsInAndConnectsClient()
    {
        $this->perforce->expects(static::at(0))->method('p4Login');
        $this->perforce->expects(static::at(1))->method('checkStream');
        $this->perforce->expects(static::at(2))->method('writeP4ClientSpec');
        $this->perforce->expects(static::at(3))->method('connectClient');
        $this->driver->initialize();
    }

    public function testPublicRepositoryWithEmptyComposer()
    {
        $identifier = 'TEST_IDENTIFIER';
        $this->perforce->expects(static::any())
            ->method('getComposerInformation')
            ->with(static::equalTo($identifier))
            ->willReturn('')
        ;

        $this->driver->initialize();
        $validEmpty = array(
            '_nonexistent_package' => true,
        );

        static::assertSame($validEmpty, $this->driver->getComposerInformation($identifier));
    }

    public function testPublicRepositoryWithCodeCache()
    {
        $identifier = 'TEST_IDENTIFIER';
        $this->perforce->expects(static::any())
            ->method('getComposerInformation')
            ->with(static::equalTo($identifier))
            ->willReturn(array('name' => 'foo'))
        ;

        $this->driver->initialize();
        $composer1 = $this->driver->getComposerInformation($identifier);
        $composer2 = $this->driver->getComposerInformation($identifier);

        static::assertNotNull($composer1);
        static::assertNotNull($composer2);
        static::assertSame($composer1, $composer2);
    }

    public function testPublicRepositoryWithFilesystemCache()
    {
        $identifier = 'TEST_IDENTIFIER';
        $this->perforce->expects(static::any())
            ->method('getComposerInformation')
            ->with(static::equalTo($identifier))
            ->willReturn(array('name' => 'foo'))
        ;

        $driver2 = new PerforceDriver($this->repoConfig, $this->io, $this->config, $this->process, $this->remoteFileSystem);
        $reflectionClass = new \ReflectionClass($driver2);
        $property = $reflectionClass->getProperty('perforce');
        $property->setAccessible(true);
        $property->setValue($driver2, $this->perforce);

        $this->driver->initialize();
        $driver2->initialize();

        $composer1 = $this->driver->getComposerInformation($identifier);
        $composer2 = $driver2->getComposerInformation($identifier);

        static::assertNotNull($composer1);
        static::assertNotNull($composer2);
        static::assertSame($composer1, $composer2);
    }

    protected function getMockIOInterface()
    {
        return $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
    }

    protected function getMockProcessExecutor()
    {
        return $this->getMockBuilder('Composer\Util\ProcessExecutor')->getMock();
    }

    protected function getMockRemoteFilesystem()
    {
        return $this->getMockBuilder('Composer\Util\RemoteFilesystem')->disableOriginalConstructor()->getMock();
    }

    protected function overrideDriverInternalPerforce(Perforce $perforce)
    {
        $reflectionClass = new \ReflectionClass($this->driver);
        $property = $reflectionClass->getProperty('perforce');
        $property->setAccessible(true);
        $property->setValue($this->driver, $perforce);
    }

    protected function getTestConfig($testPath)
    {
        $config = new Config();
        $config->merge(array('config' => array('home' => $testPath)));

        return $config;
    }

    protected function getTestRepoConfig()
    {
        return array(
            'url' => self::TEST_URL,
            'depot' => self::TEST_DEPOT,
            'branch' => self::TEST_BRANCH,
            'asset-type' => 'ASSET',
            'filename' => 'ASSET.json',
        );
    }

    protected function getMockPerforce()
    {
        $methods = array('p4login', 'checkStream', 'writeP4ClientSpec', 'connectClient', 'getComposerInformation', 'cleanupClientSpec');

        return $this->getMockBuilder('Fxp\Composer\AssetPlugin\Util\Perforce')
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock()
        ;
    }
}
