<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Repository;

use Composer\Config;
use Composer\DependencyResolver\Pool;
use Composer\Downloader\TransportException;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Repository\RepositoryManager;
use Fxp\Composer\AssetPlugin\Config\Config as AssetConfig;
use Fxp\Composer\AssetPlugin\Repository\AbstractAssetsRepository;
use Fxp\Composer\AssetPlugin\Repository\AssetRepositoryManager;
use Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository;
use Fxp\Composer\AssetPlugin\Repository\VcsPackageFilter;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Abstract class for Tests of assets repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class AbstractAssetsRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var IOInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $io;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var RepositoryManager
     */
    protected $rm;

    /**
     * @var AssetRepositoryManager
     */
    protected $assetRepositoryManager;

    /**
     * @var AbstractAssetsRepository
     */
    protected $registry;

    /**
     * @var Pool
     */
    protected $pool;

    protected function setUp()
    {
        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $io->expects(static::any())
            ->method('isVerbose')
            ->willReturn(true)
        ;
        /** @var IOInterface $io */
        $config = new Config();
        $config->merge(array(
            'config' => array(
                'home' => sys_get_temp_dir().'/composer-test',
                'cache-repo-dir' => sys_get_temp_dir().'/composer-test-repo-cache',
            ),
        ));
        /** @var VcsPackageFilter $filter */
        $filter = $this->getMockBuilder(VcsPackageFilter::class)->disableOriginalConstructor()->getMock();
        $rm = new RepositoryManager($io, $config);
        $rm->setRepositoryClass($this->getType().'-vcs', 'Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\MockAssetRepository');
        $this->assetRepositoryManager = new AssetRepositoryManager($io, $rm, new AssetConfig(array()), $filter);
        $repoConfig = array_merge(array(
            'asset-repository-manager' => $this->assetRepositoryManager,
            'asset-options' => array(
                'searchable' => true,
            ),
        ), $this->getCustomRepoConfig());

        $this->io = $io;
        $this->config = $config;
        $this->rm = $rm;
        $this->registry = $this->getRegistry($repoConfig, $io, $config);
        $this->pool = $this->getMockBuilder('Composer\DependencyResolver\Pool')->getMock();
    }

    protected function tearDown()
    {
        $this->io = null;
        $this->config = null;
        $this->rm = null;
        $this->registry = null;
        $this->pool = null;

        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/composer-test-repo-cache');
        $fs->remove(sys_get_temp_dir().'/composer-test');
    }

    public function testFindPackageMustBeAlwaysNull()
    {
        static::assertNull($this->registry->findPackage('foobar', '0'));
    }

    public function testFindPackageMustBeAlwaysEmpty()
    {
        static::assertCount(0, $this->registry->findPackages('foobar', '0'));
    }

    /**
     * @expectedException \LogicException
     */
    public function testGetPackagesNotBeUsed()
    {
        $this->registry->getPackages();
    }

    public function testGetProviderNamesMustBeEmpty()
    {
        static::assertCount(0, $this->registry->getProviderNames());
    }

    public function testGetMinimalPackagesMustBeAlwaysEmpty()
    {
        static::assertCount(0, $this->registry->getMinimalPackages());
    }

    public function testWhatProvidesWithNotAssetName()
    {
        static::assertCount(0, $this->registry->whatProvides($this->pool, 'foo/bar'));
    }

    public function testWhatProvidesWithNonExistentPackage()
    {
        $name = $this->getType().'-asset/non-existent';
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects(static::any())
            ->method('getContents')
            ->will(static::throwException(new TransportException('Package not found')))
        ;

        static::assertCount(0, $this->rm->getRepositories());
        static::assertCount(0, $this->registry->whatProvides($this->pool, $name));
        static::assertCount(0, $this->registry->whatProvides($this->pool, $name));
        static::assertCount(0, $this->rm->getRepositories());
    }

    public function testWhatProvidesWithExistingPackage()
    {
        $name = $this->getType().'-asset/existing';
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects(static::any())
            ->method('getContents')
            ->willReturn(json_encode($this->getMockPackageForVcsConfig()))
        ;

        static::assertCount(0, $this->rm->getRepositories());
        static::assertCount(0, $this->registry->whatProvides($this->pool, $name));
        static::assertCount(0, $this->registry->whatProvides($this->pool, $name));
        static::assertCount(1, $this->rm->getRepositories());
    }

    public function testWhatProvidesWithExistingAliasPackage()
    {
        $name = $this->getType().'-asset/existing-1.0';
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects(static::any())
            ->method('getContents')
            ->willReturn(json_encode($this->getMockPackageForVcsConfig()))
        ;

        static::assertCount(0, $this->rm->getRepositories());
        static::assertCount(0, $this->registry->whatProvides($this->pool, $name));
        static::assertCount(0, $this->registry->whatProvides($this->pool, $name));
        static::assertCount(1, $this->rm->getRepositories());
    }

    public function testWhatProvidesWithCamelcasePackageName()
    {
        $assetName = 'CamelCasePackage';
        $name = $this->getType().'-asset/'.strtolower($assetName);
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects(static::at(0))
            ->method('getContents')
            ->will(static::throwException(new TransportException('Package not found', 404)))
        ;
        $rfs->expects(static::at(1))
            ->method('getContents')
            ->will(static::throwException(new TransportException('Package not found', 404)))
        ;
        $rfs->expects(static::at(2))
            ->method('getContents')
            ->will(static::throwException(new TransportException('Package not found', 404)))
        ;
        $rfs->expects(static::at(3))
            ->method('getContents')
            ->willReturn(json_encode($this->getMockSearchResult($assetName)))
        ;
        $rfs->expects(static::at(4))
            ->method('getContents')
            ->willReturn(json_encode($this->getMockPackageForVcsConfig()))
        ;

        static::assertCount(0, $this->rm->getRepositories());
        static::assertCount(0, $this->registry->whatProvides($this->pool, $name));
        static::assertCount(0, $this->registry->whatProvides($this->pool, $name));
        static::assertCount(1, $this->rm->getRepositories());
    }

    public function testSearch()
    {
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects(static::any())
            ->method('getContents')
            ->willReturn(json_encode($this->getMockSearchResult()))
        ;

        $result = $this->registry->search('query');
        static::assertCount(\count($this->getMockSearchResult()), $result);
    }

    public function testSearchWithAssetComposerPrefix()
    {
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects(static::any())
            ->method('getContents')
            ->willReturn(json_encode($this->getMockSearchResult()))
        ;

        $result = $this->registry->search($this->getType().'-asset/query');
        static::assertCount(\count($this->getMockSearchResult()), $result);
    }

    public function testSearchWithSearchDisabled()
    {
        $repoConfig = array(
            'asset-repository-manager' => $this->assetRepositoryManager,
            'asset-options' => array(
                'searchable' => false,
            ),
        );
        $this->registry = $this->getRegistry($repoConfig, $this->io, $this->config);

        static::assertCount(0, $this->registry->search('query'));
    }

    public function testOverridingVcsRepositoryConfig()
    {
        $name = $this->getType().'-asset/foobar';
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects(static::any())
            ->method('getContents')
            ->willReturn(json_encode($this->getMockPackageForVcsConfig()))
        ;

        $repo = $this->getMockBuilder('Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $repo->expects(static::any())
            ->method('getComposerPackageName')
            ->willReturn($name)
        ;

        /* @var AssetVcsRepository $repo */
        $this->rm->addRepository($repo);

        static::assertCount(0, $this->registry->whatProvides($this->pool, $name));
    }

    protected function getCustomRepoConfig()
    {
        return array();
    }

    /**
     * Gets the asset type.
     *
     * @return string
     */
    abstract protected function getType();

    /**
     * Gets the asset registry.
     *
     * @param array           $repoConfig
     * @param IOInterface     $io
     * @param Config          $config
     * @param EventDispatcher $eventDispatcher
     *
     * @return AbstractAssetsRepository
     */
    abstract protected function getRegistry(array $repoConfig, IOInterface $io, Config $config, EventDispatcher $eventDispatcher = null);

    /**
     * Gets the mock package of asset for the config of VCS repository.
     *
     * @return array
     */
    abstract protected function getMockPackageForVcsConfig();

    /**
     * Gets the mock search result.
     *
     * @param string $name
     *
     * @return array
     */
    abstract protected function getMockSearchResult($name = 'mock-package');

    /**
     * Replaces the Remote file system of Registry by a mock.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function replaceRegistryRfsByMock()
    {
        $ref = new \ReflectionClass($this->registry);
        $pRef = $ref->getParentClass()->getParentClass();
        $pRfs = $pRef->getProperty('rfs');
        $pRfs->setAccessible(true);

        $rfs = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($this->io, $this->config))
            ->getMock()
        ;

        $pRfs->setValue($this->registry, $rfs);

        return $rfs;
    }
}
