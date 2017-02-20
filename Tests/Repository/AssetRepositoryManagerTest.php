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

use Composer\DependencyResolver\Pool;
use Composer\IO\IOInterface;
use Composer\Repository\RepositoryInterface;
use Composer\Repository\RepositoryManager;
use Fxp\Composer\AssetPlugin\Repository\AssetRepositoryManager;
use Fxp\Composer\AssetPlugin\Repository\ResolutionManager;
use Fxp\Composer\AssetPlugin\Repository\VcsPackageFilter;

/**
 * Tests of Asset Repository Manager.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class AssetRepositoryManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RepositoryManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rm;

    /**
     * @var IOInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $io;

    /**
     * @var VcsPackageFilter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $filter;

    /**
     * @var ResolutionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resolutionManager;

    /**
     * @var AssetRepositoryManager
     */
    protected $assertRepositoryManager;

    protected function setUp()
    {
        $this->io = $this->getMockBuilder(IOInterface::class)->getMock();
        $this->rm = $this->getMockBuilder(RepositoryManager::class)->disableOriginalConstructor()->getMock();
        $this->filter = $this->getMockBuilder(VcsPackageFilter::class)->disableOriginalConstructor()->getMock();

        $this->resolutionManager = $this->getMockBuilder(ResolutionManager::class)->getMock();
        $this->assertRepositoryManager = new AssetRepositoryManager($this->io, $this->rm, $this->filter);
    }

    public function getDataForSolveResolutions()
    {
        return array(
            array(true),
            array(false),
        );
    }

    /**
     * @dataProvider getDataForSolveResolutions
     *
     * @param bool $withResolutionManager
     */
    public function testSolveResolutions($withResolutionManager)
    {
        $expected = array(
            'name' => 'foo/bar',
        );

        if ($withResolutionManager) {
            $this->assertRepositoryManager->setResolutionManager($this->resolutionManager);
            $this->resolutionManager->expects($this->once())
                ->method('solveResolutions')
                ->with($expected)
                ->willReturn($expected);
        } else {
            $this->resolutionManager->expects($this->never())
                ->method('solveResolutions');
        }

        $data = $this->assertRepositoryManager->solveResolutions($expected);

        $this->assertSame($expected, $data);
    }

    public function testAddRepositoryInPool()
    {
        $repos = array(
            array(
                'name' => 'foo/bar',
                'type' => 'asset-vcs',
                'url' => 'https://github.com/helloguest/helloguest-ui-app.git',
            ),
        );

        $repoConfigExpected = array_merge($repos[0], array(
            'asset-repository-manager' => $this->assertRepositoryManager,
            'vcs-package-filter' => $this->filter,
        ));

        $repo = $this->getMockBuilder(RepositoryInterface::class)->getMock();

        $this->rm->expects($this->once())
            ->method('createRepository')
            ->with('asset-vcs', $repoConfigExpected)
            ->willReturn($repo);

        $this->assertRepositoryManager->addRepositories($repos);

        /* @var Pool|\PHPUnit_Framework_MockObject_MockObject $pool */
        $pool = $this->getMockBuilder(Pool::class)->disableOriginalConstructor()->getMock();
        $pool->expects($this->once())
            ->method('addRepository')
            ->with($repo);

        $this->assertRepositoryManager->setPool($pool);
    }
}
