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
use Fxp\Composer\AssetPlugin\Config\ConfigBuilder;
use Fxp\Composer\AssetPlugin\Installer\IgnoreFactory;
use Fxp\Composer\AssetPlugin\Installer\IgnoreManager;

/**
 * Tests of ignore factory.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class IgnoreFactoryTest extends \PHPUnit_Framework_TestCase
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
     * @var RootPackageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rootPackage;

    /**
     * @var PackageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $package;

    public function setUp()
    {
        $this->config = $this->getMockBuilder('Composer\Config')->getMock();
        $this->config->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($key) {
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
            }));

        $this->rootPackage = $this->getMockBuilder('Composer\Package\RootPackageInterface')->getMock();
        $this->package = $this->getMockBuilder('Composer\Package\PackageInterface')->getMock();
        $this->package->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('foo-asset/foo'));

        $this->composer = $this->getMockBuilder('Composer\Composer')->getMock();
        $this->composer->expects($this->any())
            ->method('getPackage')
            ->will($this->returnValue($this->rootPackage));
        $this->composer->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($this->config));
    }

    public function tearDown()
    {
        $this->composer = null;
        $this->config = null;
        $this->rootPackage = null;
        $this->package = null;
    }

    public function testCreateWithoutIgnoreFiles()
    {
        $config = ConfigBuilder::build($this->composer);
        $manager = IgnoreFactory::create($config, $this->composer, $this->package);

        $this->assertTrue($manager->isEnabled());
        $this->assertFalse($manager->hasPattern());
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

        $this->rootPackage->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($config));

        $config = ConfigBuilder::build($this->composer);
        $manager = IgnoreFactory::create($config, $this->composer, $this->package);

        $this->assertTrue($manager->isEnabled());
        $this->assertTrue($manager->hasPattern());
        $this->validateInstallDir($manager, $this->config->get('vendor-dir').'/'.$this->package->getName());
    }

    public function testCreateWithCustomInstallDir()
    {
        $installDir = 'web/assets/';
        $config = ConfigBuilder::build($this->composer);
        $manager = IgnoreFactory::create($config, $this->composer, $this->package, $installDir);

        $this->assertTrue($manager->isEnabled());
        $this->assertFalse($manager->hasPattern());
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

        $this->rootPackage->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($config));

        $config = ConfigBuilder::build($this->composer);
        $manager = IgnoreFactory::create($config, $this->composer, $this->package);

        $this->assertTrue($manager->isEnabled());
        $this->assertFalse($manager->hasPattern());
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

        $this->rootPackage->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($config));

        $config = ConfigBuilder::build($this->composer);
        $manager = IgnoreFactory::create($config, $this->composer, $this->package);

        $this->assertFalse($manager->isEnabled());
        $this->assertFalse($manager->hasPattern());
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

        $this->rootPackage->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($config));

        $config = ConfigBuilder::build($this->composer);
        $manager = IgnoreFactory::create($config, $this->composer, $this->package, null, 'custom-ignore-files');

        $this->assertTrue($manager->isEnabled());
        $this->assertTrue($manager->hasPattern());
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

        $this->assertSame($installDir, $prop->getValue($manager));
    }
}
