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

use Composer\IO\IOInterface;
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
     * @var ResolutionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resolutionManager;

    /**
     * @var AssetRepositoryManager
     */
    protected $assertRepositoryManager;

    protected function setUp()
    {
        /* @var IOInterface|\PHPUnit_Framework_MockObject_MockObject $io */
        $io = $this->getMockBuilder(IOInterface::class)->getMock();
        /* @var RepositoryManager|\PHPUnit_Framework_MockObject_MockObject $rm */
        $rm = $this->getMockBuilder(RepositoryManager::class)->disableOriginalConstructor()->getMock();
        /* @var VcsPackageFilter|\PHPUnit_Framework_MockObject_MockObject $filter */
        $filter = $this->getMockBuilder(VcsPackageFilter::class)->disableOriginalConstructor()->getMock();

        $this->resolutionManager = $this->getMockBuilder(ResolutionManager::class)->getMock();
        $this->assertRepositoryManager = new AssetRepositoryManager($io, $rm, $filter);
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
}
