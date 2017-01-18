<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Util;

use Composer\IO\IOInterface;
use Composer\Test\Util\PerforceTest as BasePerforceTest;
use Composer\Util\Filesystem;
use Composer\Util\ProcessExecutor;
use Fxp\Composer\AssetPlugin\Util\Perforce;

/**
 * Tests for the perforce.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class PerforceTest extends BasePerforceTest
{
    /**
     * @var Perforce
     */
    protected $perforce;

    /**
     * @var ProcessExecutor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $processExecutor;

    /**
     * @var array
     */
    protected $repoConfig;

    protected function setUp()
    {
        $this->processExecutor = $this->getMockBuilder('Composer\Util\ProcessExecutor')->getMock();
        $this->repoConfig = $this->getTestRepoConfig();
        $this->io = $this->getMockIOInterface();
        $this->createNewPerforceWithWindowsFlag(true);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $fs = new Filesystem();
        $fs->remove($this::TEST_PATH);
    }

    /**
     * @return IOInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getMockIOInterface()
    {
        return $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
    }

    public function testQueryP4PasswordWithPasswordAlreadySet()
    {
        $repoConfig = array(
            'depot' => 'depot',
            'branch' => 'branch',
            'p4user' => 'user',
            'p4password' => 'TEST_PASSWORD',
            'filename' => 'ASSET.json',
        );
        $this->perforce = new Perforce($repoConfig, 'port', 'path', $this->processExecutor, false, $this->getMockIOInterface(), 'TEST');
        $password = $this->perforce->queryP4Password();
        $this->assertEquals('TEST_PASSWORD', $password);
    }

    public function getTestRepoConfig()
    {
        return array_merge(parent::getTestRepoConfig(), array(
            'filename' => 'ASSET.json',
        ));
    }

    public function testGetComposerInformationWithoutLabelWithoutStream()
    {
        $expectedCommand = 'p4 -u user -c composer_perforce_TEST_depot -p port  print //depot/ASSET.json';
        $this->processExecutor->expects($this->at(0))
            ->method('execute')
            ->with($this->equalTo($expectedCommand))
            ->will(
                $this->returnCallback(
                    function ($command, &$output) {
                        $output = PerforceTest::getComposerJson();

                        return $command ? true : true;
                    }
                )
            );

        $result = $this->perforce->getComposerInformation('//depot');
        $expected = array(
            'name' => 'test/perforce',
            'description' => 'Basic project for testing',
            'minimum-stability' => 'dev',
            'autoload' => array('psr-0' => array()),
        );
        $this->assertEquals($expected, $result);
    }

    public function testGetComposerInformationWithLabelWithoutStream()
    {
        $expectedCommand = 'p4 -u user -p port  files //depot/ASSET.json@0.0.1';
        $this->processExecutor->expects($this->at(0))
            ->method('execute')
            ->with($this->equalTo($expectedCommand))
            ->will(
                $this->returnCallback(
                    function ($command, &$output) {
                        $output = '//depot/ASSET.json#1 - branch change 10001 (text)';

                        return $command ? true : true;
                    }
                )
            );

        $expectedCommand = 'p4 -u user -c composer_perforce_TEST_depot -p port  print //depot/ASSET.json@10001';
        $this->processExecutor->expects($this->at(1))
            ->method('execute')
            ->with($this->equalTo($expectedCommand))
            ->will(
                $this->returnCallback(
                    function ($command, &$output) {
                        $output = PerforceTest::getComposerJson();

                        return $command ? true : true;
                    }
                )
            );

        $result = $this->perforce->getComposerInformation('//depot@0.0.1');

        $expected = array(
            'name' => 'test/perforce',
            'description' => 'Basic project for testing',
            'minimum-stability' => 'dev',
            'autoload' => array('psr-0' => array()),
        );
        $this->assertEquals($expected, $result);
    }

    public function testGetComposerInformationWithoutLabelWithStream()
    {
        $this->setAssetPerforceToStream();

        $expectedCommand = 'p4 -u user -c composer_perforce_TEST_depot_branch -p port  print //depot/branch/ASSET.json';
        $this->processExecutor->expects($this->at(0))
            ->method('execute')
            ->with($this->equalTo($expectedCommand))
            ->will(
                $this->returnCallback(
                    function ($command, &$output) {
                        $output = PerforceTest::getComposerJson();

                        return $command ? true : true;
                    }
                )
            );

        $result = $this->perforce->getComposerInformation('//depot/branch');

        $expected = array(
            'name' => 'test/perforce',
            'description' => 'Basic project for testing',
            'minimum-stability' => 'dev',
            'autoload' => array('psr-0' => array()),
        );
        $this->assertEquals($expected, $result);
    }

    public function testGetComposerInformationWithLabelWithStream()
    {
        $this->setAssetPerforceToStream();
        $expectedCommand = 'p4 -u user -p port  files //depot/branch/ASSET.json@0.0.1';
        $this->processExecutor->expects($this->at(0))
            ->method('execute')
            ->with($this->equalTo($expectedCommand))
            ->will(
                $this->returnCallback(
                    function ($command, &$output) {
                        $output = '//depot/ASSET.json#1 - branch change 10001 (text)';

                        return $command ? true : true;
                    }
                )
            );

        $expectedCommand = 'p4 -u user -c composer_perforce_TEST_depot_branch -p port  print //depot/branch/ASSET.json@10001';
        $this->processExecutor->expects($this->at(1))
            ->method('execute')
            ->with($this->equalTo($expectedCommand))
            ->will(
                $this->returnCallback(
                    function ($command, &$output) {
                        $output = PerforceTest::getComposerJson();

                        return $command ? true : true;
                    }
                )
            );

        $result = $this->perforce->getComposerInformation('//depot/branch@0.0.1');

        $expected = array(
            'name' => 'test/perforce',
            'description' => 'Basic project for testing',
            'minimum-stability' => 'dev',
            'autoload' => array('psr-0' => array()),
        );
        $this->assertEquals($expected, $result);
    }

    public function testGetComposerInformationWithLabelButNoSuchFile()
    {
        $this->setAssetPerforceToStream();
        $expectedCommand = 'p4 -u user -p port  files //depot/branch/ASSET.json@0.0.1';
        $this->processExecutor->expects($this->at(0))
            ->method('execute')
            ->with($this->equalTo($expectedCommand))
            ->will(
                $this->returnCallback(
                    function ($command, &$output) {
                        $output = 'no such file(s).';

                        return $command ? true : true;
                    }
                )
            );

        $result = $this->perforce->getComposerInformation('//depot/branch@0.0.1');

        $this->assertNull($result);
    }

    public function testGetComposerInformationWithLabelWithStreamWithNoChange()
    {
        $this->setAssetPerforceToStream();
        $expectedCommand = 'p4 -u user -p port  files //depot/branch/ASSET.json@0.0.1';
        $this->processExecutor->expects($this->at(0))
            ->method('execute')
            ->with($this->equalTo($expectedCommand))
            ->will(
                $this->returnCallback(
                    function ($command, &$output) {
                        $output = '//depot/ASSET.json#1 - branch 10001 (text)';

                        return $command ? true : true;
                    }
                )
            );

        $result = $this->perforce->getComposerInformation('//depot/branch@0.0.1');

        $this->assertNull($result);
    }

    public function testCheckServerExists()
    {
        /* @var ProcessExecutor|\PHPUnit_Framework_MockObject_MockObject $processExecutor */
        $processExecutor = $this->getMockBuilder('Composer\Util\ProcessExecutor')->getMock();

        $expectedCommand = 'p4 -p perforce.does.exist:port info -s';
        $processExecutor->expects($this->at(0))
            ->method('execute')
            ->with($this->equalTo($expectedCommand), $this->equalTo(null))
            ->will($this->returnValue(0));

        $result = $this->perforce->checkServerExists('perforce.does.exist:port', $processExecutor);
        $this->assertTrue($result);
    }

    /**
     * Test if "p4" command is missing.
     *
     * @covers \Composer\Util\Perforce::checkServerExists
     */
    public function testCheckServerClientError()
    {
        /* @var ProcessExecutor|\PHPUnit_Framework_MockObject_MockObject $processExecutor */
        $processExecutor = $this->getMockBuilder('Composer\Util\ProcessExecutor')->getMock();

        $expectedCommand = 'p4 -p perforce.does.exist:port info -s';
        $processExecutor->expects($this->at(0))
            ->method('execute')
            ->with($this->equalTo($expectedCommand), $this->equalTo(null))
            ->will($this->returnValue(127));

        $result = $this->perforce->checkServerExists('perforce.does.exist:port', $processExecutor);
        $this->assertFalse($result);
    }

    public function testCleanupClientSpecShouldDeleteClient()
    {
        /* @var Filesystem|\PHPUnit_Framework_MockObject_MockObject $fs */
        $fs = $this->getMockBuilder('Composer\Util\Filesystem')->getMock();
        $this->perforce->setFilesystem($fs);

        $testClient = $this->perforce->getClient();
        $expectedCommand = 'p4 -u '.self::TEST_P4USER.' -p '.self::TEST_PORT.' client -d '.$testClient;
        $this->processExecutor->expects($this->once())->method('execute')->with($this->equalTo($expectedCommand));

        $fs->expects($this->once())->method('remove')->with($this->perforce->getP4ClientSpec());

        $this->perforce->cleanupClientSpec();
    }

    protected function createNewPerforceWithWindowsFlag($flag)
    {
        $this->perforce = new Perforce($this->repoConfig, self::TEST_PORT, self::TEST_PATH, $this->processExecutor, $flag, $this->io);
    }

    private function setAssetPerforceToStream()
    {
        $this->perforce->setStream('//depot/branch');
    }
}
