<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests;

use Composer\Composer;
use Composer\Config;
use Composer\DependencyResolver\Pool;
use Composer\Installer\InstallationManager;
use Composer\Installer\InstallerEvent;
use Composer\IO\IOInterface;
use Composer\Plugin\CommandEvent;
use Composer\Repository\RepositoryManager;
use Composer\Util\Filesystem;
use Fxp\Composer\AssetPlugin\FxpAssetPlugin;

/**
 * Tests of asset plugin.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class FxpAssetPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FxpAssetPlugin
     */
    protected $plugin;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $package;

    protected function setUp()
    {
        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $config = $this->getMockBuilder('Composer\Config')->getMock();
        $config->expects(static::any())
            ->method('get')
            ->willReturnCallback(function ($key) {
                $value = null;

                switch ($key) {
                    case 'cache-repo-dir':
                        $value = sys_get_temp_dir().'/composer-test-repo-cache';

                        break;
                }

                return $value;
            })
        ;
        $this->package = $this->getMockBuilder('Composer\Package\RootPackageInterface')->getMock();
        $this->package->expects(static::any())
            ->method('getRequires')
            ->willReturn(array())
        ;
        $this->package->expects(static::any())
            ->method('getDevRequires')
            ->willReturn(array())
        ;

        /** @var IOInterface $io */
        /** @var Config $config */
        $rm = new RepositoryManager($io, $config);
        $im = new InstallationManager();

        $composer = $this->getMockBuilder('Composer\Composer')->getMock();
        $composer->expects(static::any())
            ->method('getRepositoryManager')
            ->willReturn($rm)
        ;
        $composer->expects(static::any())
            ->method('getPackage')
            ->willReturn($this->package)
        ;
        $composer->expects(static::any())
            ->method('getConfig')
            ->willReturn($config)
        ;
        $composer->expects(static::any())
            ->method('getInstallationManager')
            ->willReturn($im)
        ;

        $this->plugin = new FxpAssetPlugin();
        $this->composer = $composer;
        $this->io = $io;
    }

    protected function tearDown()
    {
        $this->plugin = null;
        $this->composer = null;
        $this->io = null;

        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/composer-test-repo-cache');
    }

    public function testAssetRepositories()
    {
        $this->package->expects(static::any())
            ->method('getConfig')
            ->willReturn(array(
                'fxp-asset' => array(
                    'private-bower-registries' => array(
                        'my-private-bower-server' => 'https://my-private-bower-server.tld/packages',
                    ),
                ),
            ))
        ;

        $this->plugin->activate($this->composer, $this->io);
        $repos = $this->composer->getRepositoryManager()->getRepositories();

        static::assertCount(3, $repos);
        foreach ($repos as $repo) {
            static::assertInstanceOf('Composer\Repository\ComposerRepository', $repo);
        }
    }

    /**
     * @dataProvider getDataForAssetVcsRepositories
     *
     * @param string $type
     */
    public function testAssetVcsRepositories($type)
    {
        $this->package->expects(static::any())
            ->method('getExtra')
            ->willReturn(array())
        ;

        $this->plugin->activate($this->composer, $this->io);
        $rm = $this->composer->getRepositoryManager();
        $repo = $rm->createRepository($type, array(
            'type' => $type,
            'url' => 'http://foo.tld',
            'name' => 'foo',
        ));

        static::assertInstanceOf('Composer\Repository\VcsRepository', $repo);
    }

    public function getDataForAssetVcsRepositories()
    {
        return array(
            array('npm-vcs'),
            array('npm-git'),
            array('npm-github'),

            array('bower-vcs'),
            array('bower-git'),
            array('bower-github'),
        );
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testAssetRepositoryWithValueIsNotArray()
    {
        $this->package->expects(static::any())
            ->method('getExtra')
            ->willReturn(array('asset-repositories' => array(
                'invalid_repo',
            )))
        ;

        $this->plugin->activate($this->composer, $this->io);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testAssetRepositoryWithInvalidType()
    {
        $this->package->expects(static::any())
            ->method('getExtra')
            ->willReturn(array('asset-repositories' => array(
                array(),
            )))
        ;

        $this->plugin->activate($this->composer, $this->io);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testAssetRepositoryWithInvalidTypeFormat()
    {
        $this->package->expects(static::any())
            ->method('getExtra')
            ->willReturn(array('asset-repositories' => array(
                array('type' => 'invalid_type'),
            )))
        ;

        $this->plugin->activate($this->composer, $this->io);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testAssetRepositoryWithInvalidUrl()
    {
        $this->package->expects(static::any())
            ->method('getExtra')
            ->willReturn(array('asset-repositories' => array(
                array('type' => 'npm-vcs'),
            )))
        ;

        $this->plugin->activate($this->composer, $this->io);
    }

    public function testAssetRepository()
    {
        $this->package->expects(static::any())
            ->method('getExtra')
            ->willReturn(array('asset-repositories' => array(
                array('type' => 'npm-vcs', 'url' => 'http://foo.tld', 'name' => 'foo'),
            )))
        ;

        $this->plugin->activate($this->composer, $this->io);
        $repos = $this->composer->getRepositoryManager()->getRepositories();

        static::assertCount(3, $repos);
        static::assertInstanceOf('Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository', $repos[2]);
    }

    public function testAssetRepositoryWithAlreadyExistRepositoryName()
    {
        $this->package->expects(static::any())
            ->method('getExtra')
            ->willReturn(array('asset-repositories' => array(
                array('type' => 'npm-vcs', 'url' => 'http://foo.tld', 'name' => 'foo'),
                array('type' => 'npm-vcs', 'url' => 'http://foo.tld', 'name' => 'foo'),
            )))
        ;

        $this->plugin->activate($this->composer, $this->io);
        $repos = $this->composer->getRepositoryManager()->getRepositories();

        static::assertCount(3, $repos);
        static::assertInstanceOf('Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository', $repos[2]);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testAssetPackageWithoutPackage()
    {
        $this->package->expects(static::any())
            ->method('getExtra')
            ->willReturn(array('asset-repositories' => array(
                array('type' => 'package'),
            )))
        ;

        $this->plugin->activate($this->composer, $this->io);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testAssetPackageWithInvalidPackage()
    {
        $this->package->expects(static::any())
            ->method('getExtra')
            ->willReturn(array('asset-repositories' => array(
                array('type' => 'package', 'package' => array('key' => 'value')),
            )))
        ;

        $this->plugin->activate($this->composer, $this->io);
    }

    public function testAssetPackageRepositories()
    {
        $this->package->expects(static::any())
            ->method('getExtra')
            ->willReturn(array('asset-repositories' => array(
                array(
                    'type' => 'package',
                    'package' => array(
                        'name' => 'foo',
                        'type' => 'ASSET-asset-library',
                        'version' => '0.0.0.0',
                        'dist' => array(
                            'url' => 'foo.tld/bar',
                            'type' => 'file',
                        ),
                    ),
                ),
            )))
        ;

        $rm = $this->composer->getRepositoryManager();
        $rm->setRepositoryClass('package', 'Composer\Repository\PackageRepository');
        $this->plugin->activate($this->composer, $this->io);
        $repos = $this->composer->getRepositoryManager()->getRepositories();

        static::assertCount(3, $repos);
        static::assertInstanceOf('Composer\Repository\PackageRepository', $repos[2]);
    }

    public function testOptionsForAssetRegistryRepositories()
    {
        $this->package->expects(static::any())
            ->method('getConfig')
            ->willReturn(array(
                'fxp-asset' => array(
                    'registry-options' => array(
                        'npm-option1' => 'value 1',
                        'bower-option1' => 'value 2',
                    ),
                ),
            ))
        ;
        static::assertInstanceOf('Composer\Package\RootPackageInterface', $this->package);

        $this->plugin->activate($this->composer, $this->io);
    }

    public function testSubscribeEvents()
    {
        $this->package->expects(static::any())
            ->method('getExtra')
            ->willReturn(array())
        ;

        static::assertCount(2, $this->plugin->getSubscribedEvents());
        static::assertCount(0, $this->composer->getRepositoryManager()->getRepositories());

        /** @var InstallerEvent|\PHPUnit_Framework_MockObject_MockObject $eventInstaller */
        $eventInstaller = $this->getMockBuilder('Composer\Installer\InstallerEvent')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $eventInstaller->expects(static::any())
            ->method('getPool')
            ->willReturn(
                $this->getMockBuilder(Pool::class)
                    ->disableOriginalConstructor()
                    ->getMock()
            )
        ;
        /** @var CommandEvent|\PHPUnit_Framework_MockObject_MockObject $eventCommand */
        $eventCommand = $this->getMockBuilder('Composer\Plugin\CommandEvent')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $eventCommand->expects(static::any())
            ->method('getCommandName')
            ->willReturn('show')
        ;

        $this->plugin->activate($this->composer, $this->io);
        $this->plugin->onPluginCommand($eventCommand);
        $this->plugin->onPreDependenciesSolving($eventInstaller);
    }

    public function testAssetInstallers()
    {
        $this->package->expects(static::any())
            ->method('getExtra')
            ->willReturn(array())
        ;

        $this->plugin->activate($this->composer, $this->io);
        $im = $this->composer->getInstallationManager();

        static::assertInstanceOf('Fxp\Composer\AssetPlugin\Installer\BowerInstaller', $im->getInstaller('bower-asset-library'));
        static::assertInstanceOf('Fxp\Composer\AssetPlugin\Installer\AssetInstaller', $im->getInstaller('npm-asset-library'));
    }

    public function testGetConfig()
    {
        $this->plugin->activate($this->composer, $this->io);

        $config = $this->plugin->getConfig();
        static::assertInstanceOf(\Fxp\Composer\AssetPlugin\Config\Config::class, $config);
    }
}
