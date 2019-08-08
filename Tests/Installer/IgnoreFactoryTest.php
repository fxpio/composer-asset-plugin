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
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Util\Filesystem;
use Fxp\Composer\AssetPlugin\Config\ConfigBuilder;
use Fxp\Composer\AssetPlugin\Installer\IgnoreFactory;
use Fxp\Composer\AssetPlugin\Installer\IgnoreManager;

/**
 * Tests of ignore factory.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class IgnoreFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Composer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $composer;

    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RootPackageInterface
     */
    protected $rootPackage;

    /**
     * @var PackageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $package;

    protected function setUp()
    {
        $this->config = $this->getMockBuilder('Composer\Config')->getMock();
        $this->config->expects(static::any())
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

        $this->rootPackage = $this->getMockBuilder('Composer\Package\RootPackageInterface')->getMock();
        $this->package = $this->getMockBuilder('Composer\Package\PackageInterface')->getMock();
        $this->package->expects(static::any())
            ->method('getName')
            ->willReturn('foo-asset/foo')
        ;

        $this->composer = $this->getMockBuilder('Composer\Composer')->getMock();
        $this->composer->expects(static::any())
            ->method('getPackage')
            ->willReturn($this->rootPackage)
        ;
        $this->composer->expects(static::any())
            ->method('getConfig')
            ->willReturn($this->config)
        ;
    }

    protected function tearDown()
    {
        $this->composer = null;
        $this->config = null;
        $this->rootPackage = null;
        $this->package = null;

        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/composer-test-repo-cache');
        $fs->remove(sys_get_temp_dir().'/composer-test');
    }

    public function testCreateWithoutIgnoreFiles()
    {
        $config = ConfigBuilder::build($this->composer);
        $manager = IgnoreFactory::create($config, $this->composer, $this->package);

        static::assertTrue($manager->isEnabled());
        static::assertFalse($manager->hasPattern());
        $this->validateInstallDir($manager, $this->config->get('vendor-dir').'/'.$this->package->getName());
    }

    public function testCreateWithIgnoreFiles()
    {
        $config = array(
            'fxp-asset' => array(
                'ignore-files' => array(
                    'foo-asset/foo' => array(
                        'PATTERN',
                    ),
                    'foo-asset/bar' => array(),
                ),
            ),
        );

        $this->rootPackage->expects(static::any())
            ->method('getConfig')
            ->willReturn($config)
        ;

        $config = ConfigBuilder::build($this->composer);
        $manager = IgnoreFactory::create($config, $this->composer, $this->package);

        static::assertTrue($manager->isEnabled());
        static::assertTrue($manager->hasPattern());
        $this->validateInstallDir($manager, $this->config->get('vendor-dir').'/'.$this->package->getName());
    }

    public function testCreateWithCustomInstallDir()
    {
        $installDir = 'web/assets/';
        $config = ConfigBuilder::build($this->composer);
        $manager = IgnoreFactory::create($config, $this->composer, $this->package, $installDir);

        static::assertTrue($manager->isEnabled());
        static::assertFalse($manager->hasPattern());
        $this->validateInstallDir($manager, rtrim($installDir, '/'));
    }

    public function testCreateWithEnablingOfIgnoreFiles()
    {
        $config = array(
            'fxp-asset' => array(
                'ignore-files' => array(
                    'foo-asset/foo' => true,
                    'foo-asset/bar' => array(),
                ),
            ),
        );

        $this->rootPackage->expects(static::any())
            ->method('getConfig')
            ->willReturn($config)
        ;

        $config = ConfigBuilder::build($this->composer);
        $manager = IgnoreFactory::create($config, $this->composer, $this->package);

        static::assertTrue($manager->isEnabled());
        static::assertFalse($manager->hasPattern());
        $this->validateInstallDir($manager, $this->config->get('vendor-dir').'/'.$this->package->getName());
    }

    public function testCreateWithDisablingOfIgnoreFiles()
    {
        $config = array(
            'fxp-asset' => array(
                'ignore-files' => array(
                    'foo-asset/foo' => false,
                    'foo-asset/bar' => array(),
                ),
            ),
        );

        $this->rootPackage->expects(static::any())
            ->method('getConfig')
            ->willReturn($config)
        ;

        $config = ConfigBuilder::build($this->composer);
        $manager = IgnoreFactory::create($config, $this->composer, $this->package);

        static::assertFalse($manager->isEnabled());
        static::assertFalse($manager->hasPattern());
        $this->validateInstallDir($manager, $this->config->get('vendor-dir').'/'.$this->package->getName());
    }

    public function testCreateWithCustomIgnoreSection()
    {
        $config = array(
            'fxp-asset' => array(
                'custom-ignore-files' => array(
                    'foo-asset/foo' => array(
                        'PATTERN',
                    ),
                    'foo-asset/bar' => array(),
                ),
            ),
        );

        $this->rootPackage->expects(static::any())
            ->method('getConfig')
            ->willReturn($config)
        ;

        $config = ConfigBuilder::build($this->composer);
        $manager = IgnoreFactory::create($config, $this->composer, $this->package, null, 'custom-ignore-files');

        static::assertTrue($manager->isEnabled());
        static::assertTrue($manager->hasPattern());
        $this->validateInstallDir($manager, $this->config->get('vendor-dir').'/'.$this->package->getName());
    }

    /**
     * @param IgnoreManager $manager
     * @param string        $installDir
     */
    protected function validateInstallDir(IgnoreManager $manager, $installDir)
    {
        $ref = new \ReflectionClass($manager);
        $prop = $ref->getProperty('installDir');
        $prop->setAccessible(true);

        static::assertSame($installDir, $prop->getValue($manager));
    }
}
