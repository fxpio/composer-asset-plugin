<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\PolicyInterface;
use Composer\DependencyResolver\Pool;
use Composer\DependencyResolver\Request;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginManager;
use Composer\Repository\CompositeRepository;
use Fxp\Composer\AssetPlugin\Composer\ScriptHandler;
use Fxp\Composer\AssetPlugin\Config\Config;
use Fxp\Composer\AssetPlugin\FxpAssetPlugin;

/**
 * Tests for the composer script handler.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class ScriptHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Composer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $composer;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var IOInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $io;

    /**
     * @var OperationInterface|InstallOperation|UpdateOperation|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $operation;

    /**
     * @var PackageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $package;

    public function setUp()
    {
        $this->composer = $this->getMockBuilder('Composer\Composer')->getMock();
        $this->io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $this->package = $this->getMockBuilder('Composer\Package\PackageInterface')->getMock();

        $this->config = $this->getMockBuilder('Composer\Config')->getMock();
        $this->config->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($key) {
                $val = null;

                switch ($key) {
                    case 'cache-repo-dir':
                        return sys_get_temp_dir().'/composer-test-repo-cache';
                    case 'vendor-dir':
                        return sys_get_temp_dir().'/composer-test/vendor';
                }

                return $val;
            }));

        $rootPackage = $this->getMockBuilder('Composer\Package\RootPackageInterface')->getMock();

        $this->composer->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($this->config));
        $this->composer->expects($this->any())
            ->method('getPackage')
            ->will($this->returnValue($rootPackage));

        $plugin = $this->getMockBuilder(FxpAssetPlugin::class)->disableOriginalConstructor()->getMock();
        $plugin->expects($this->any())
            ->method('getConfig')
            ->willReturn(new Config(array()));

        $pm = $this->getMockBuilder(PluginManager::class)->disableOriginalConstructor()->getMock();
        $pm->expects($this->any())
            ->method('getPlugins')
            ->willReturn(array($plugin));

        $this->composer->expects($this->any())
            ->method('getPluginManager')
            ->will($this->returnValue($pm));
    }

    public function tearDown()
    {
        $this->composer = null;
        $this->io = null;
        $this->operation = null;
        $this->package = null;
    }

    public function getPackageComposerTypes()
    {
        return array(
            array('npm-asset-library'),
            array('bower-asset-library'),
            array('library'),
        );
    }

    /**
     * @dataProvider getPackageComposerTypes
     *
     * @param string $composerType
     */
    public function testDeleteIgnoreFiles($composerType)
    {
        $this->operation = $this->getMockBuilder('Composer\DependencyResolver\Operation\OperationInterface')->getMock();
        $this->assertInstanceOf('Composer\DependencyResolver\Operation\OperationInterface', $this->operation);

        ScriptHandler::deleteIgnoredFiles($this->createEvent($composerType));
    }

    /**
     * @dataProvider getPackageComposerTypes
     *
     * @param string $composerType
     */
    public function testDeleteIgnoreFilesWithInstallOperation($composerType)
    {
        $this->operation = $this->getMockBuilder('Composer\DependencyResolver\Operation\InstallOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertInstanceOf('Composer\DependencyResolver\Operation\OperationInterface', $this->operation);

        ScriptHandler::deleteIgnoredFiles($this->createEvent($composerType));
    }

    /**
     * @dataProvider getPackageComposerTypes
     *
     * @param string $composerType
     */
    public function testDeleteIgnoreFilesWithUpdateOperation($composerType)
    {
        $this->operation = $this->getMockBuilder('Composer\DependencyResolver\Operation\UpdateOperation')
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertInstanceOf('Composer\DependencyResolver\Operation\OperationInterface', $this->operation);

        ScriptHandler::deleteIgnoredFiles($this->createEvent($composerType));
    }

    /**
     * @dataProvider getPackageComposerTypes
     *
     * @param string $composerType
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The fxp composer asset plugin is not found
     */
    public function testGetConfig($composerType)
    {
        $rootPackage = $this->getMockBuilder('Composer\Package\RootPackageInterface')->getMock();

        $this->composer = $this->getMockBuilder('Composer\Composer')->getMock();
        $this->composer->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($this->config));
        $this->composer->expects($this->any())
            ->method('getPackage')
            ->will($this->returnValue($rootPackage));

        $pm = $this->getMockBuilder(PluginManager::class)->disableOriginalConstructor()->getMock();
        $pm->expects($this->any())
            ->method('getPlugins')
            ->willReturn(array());

        $this->composer->expects($this->any())
            ->method('getPluginManager')
            ->will($this->returnValue($pm));

        $this->operation = $this->getMockBuilder('Composer\DependencyResolver\Operation\OperationInterface')->getMock();

        ScriptHandler::getConfig($this->createEvent($composerType));
    }

    /**
     * @param string $composerType
     *
     * @return PackageEvent
     */
    protected function createEvent($composerType)
    {
        $this->package->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($composerType));

        if ($this->operation instanceof UpdateOperation) {
            $this->operation->expects($this->any())
                ->method('getTargetPackage')
                ->will($this->returnValue($this->package));
        }

        if ($this->operation instanceof InstallOperation) {
            $this->operation->expects($this->any())
                ->method('getPackage')
                ->will($this->returnValue($this->package));
        }

        /* @var PolicyInterface $policy */
        $policy = $this->getMockBuilder('Composer\DependencyResolver\PolicyInterface')->getMock();
        /* @var Pool $pool */
        $pool = $this->getMockBuilder('Composer\DependencyResolver\Pool')->disableOriginalConstructor()->getMock();
        /* @var CompositeRepository $installedRepo */
        $installedRepo = $this->getMockBuilder('Composer\Repository\CompositeRepository')->disableOriginalConstructor()->getMock();
        /* @var Request $request */
        $request = $this->getMockBuilder('Composer\DependencyResolver\Request')->disableOriginalConstructor()->getMock();
        $operations = array($this->getMockBuilder('Composer\DependencyResolver\Operation\OperationInterface')->getMock());

        return new PackageEvent('foo-event', $this->composer, $this->io, true, $policy, $pool, $installedRepo, $request, $operations, $this->operation);
    }
}
