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
use Composer\IO\IOInterface;
use Composer\Repository\RepositoryManager;
use Fxp\Composer\AssetPlugin\FxpAssetPlugin;

/**
 * Tests of asset plugin.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class FxpAssetPluginTest extends \PHPUnit_Framework_TestCase
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
        $io = $this->getMock('Composer\IO\IOInterface');
        $config = $this->getMock('Composer\Config');
        $this->package = $this->getMock('Composer\Package\PackageInterface');

        /* @var IOInterface $io */
        /* @var Config $config */
        $rm = new RepositoryManager($io, $config);

        $composer = $this->getMock('Composer\Composer');
        $composer->expects($this->any())
            ->method('getRepositoryManager')
            ->will($this->returnValue($rm));
        $composer->expects($this->any())
            ->method('getPackage')
            ->will($this->returnValue($this->package));

        $this->plugin = new FxpAssetPlugin();
        $this->composer = $composer;
        $this->io = $io;
    }

    protected function tearnDown()
    {
        $this->plugin = null;
        $this->composer = null;
        $this->io = null;
    }

    public function testAssetRepositories()
    {
        $this->plugin->activate($this->composer, $this->io);
        $repos = $this->composer->getRepositoryManager()->getRepositories();

        $this->assertCount(2, $repos);
        foreach ($repos as $repo) {
            $this->assertInstanceOf('Composer\Repository\ComposerRepository', $repo);
        }
    }

    /**
     * @dataProvider getDataForAssetVcsRepositories
     */
    public function testAssetVcsRepositories($type)
    {
        $this->plugin->activate($this->composer, $this->io);
        $rm = $this->composer->getRepositoryManager();
        $repo = $rm->createRepository($type, array(
            'type' => $type,
            'url'  => 'http://foo.tld'
        ));

        $this->assertInstanceOf('Composer\Repository\VcsRepository', $repo);
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

    public function testAssetRepositoryWithValueIsNotArray()
    {
        $this->setExpectedException('UnexpectedValueException');

        $this->package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array('asset-repositories' => array(
                'invalid_repo'
            ))));

        $this->plugin->activate($this->composer, $this->io);
    }

    public function testAssetRepositoryWithInvalidType()
    {
        $this->setExpectedException('UnexpectedValueException');

        $this->package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array('asset-repositories' => array(
                array()
            ))));

        $this->plugin->activate($this->composer, $this->io);
    }

    public function testAssetRepositoryWithInvalidTypeFormat()
    {
        $this->setExpectedException('UnexpectedValueException');

        $this->package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array('asset-repositories' => array(
                array('type' => 'invalid_type')
            ))));

        $this->plugin->activate($this->composer, $this->io);
    }

    public function testAssetRepository()
    {
        $this->package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array('asset-repositories' => array(
                array('type' => 'npm-vcs', 'url' => 'http://foo.tld')
            ))));

        $this->plugin->activate($this->composer, $this->io);
        $repos = $this->composer->getRepositoryManager()->getRepositories();

        $this->assertCount(3, $repos);
        $this->assertInstanceOf('Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository', $repos[2]);
    }

    public function testAssetRepositoryWithAlreadyExistRepositoryName()
    {
        $this->package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue(array('asset-repositories' => array(
                array('type' => 'npm-vcs', 'url' => 'http://foo.tld'),
                array('type' => 'npm-vcs', 'url' => 'http://foo.tld')
            ))));

        $this->plugin->activate($this->composer, $this->io);
        $repos = $this->composer->getRepositoryManager()->getRepositories();

        $this->assertCount(4, $repos);
        $this->assertInstanceOf('Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository', $repos[2]);
        $this->assertInstanceOf('Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository', $repos[3]);
    }
}
