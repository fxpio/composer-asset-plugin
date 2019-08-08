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
use Composer\Util\Filesystem;
use Composer\Util\ProcessExecutor;
use Fxp\Composer\AssetPlugin\Tests\ComposerUtil;
use Fxp\Composer\AssetPlugin\Util\Perforce;

/**
 * Tests for the perforce.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class PerforceTest extends \PHPUnit\Framework\TestCase
{
    const TEST_DEPOT = 'depot';
    const TEST_BRANCH = 'branch';
    const TEST_P4USER = 'user';
    const TEST_CLIENT_NAME = 'TEST';
    const TEST_PORT = 'port';
    const TEST_PATH = 'path';
    /**
     * @var Perforce
     */
    protected $perforce;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProcessExecutor
     */
    protected $processExecutor;

    /**
     * @var array
     */
    protected $repoConfig;

    /**
     * @var IOInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $io;

    protected function setUp()
    {
        $this->processExecutor = $this->getMockBuilder('Composer\Util\ProcessExecutor')->getMock();
        $this->repoConfig = $this->getTestRepoConfig();
        $this->io = $this->getMockIOInterface();
        $this->createNewPerforceWithWindowsFlag(true);
    }

    protected function tearDown()
    {
        $this->perforce = null;
        $this->io = null;
        $this->repoConfig = null;
        $this->processExecutor = null;

        $fs = new Filesystem();
        $fs->remove($this::TEST_PATH);
    }

    public static function getComposerJson()
    {
        $composer_json = array(
            '{',
            '"name": "test/perforce",',
            '"description": "Basic project for testing",',
            '"minimum-stability": "dev",',
            '"autoload": {',
            '"psr-0" : {',
            '}',
            '}',
            '}',
        );

        return implode('', $composer_json);
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
        $this->perforce = new Perforce($repoConfig, 'port', 'path', $this->processExecutor, false, $this->getMockIOInterface());
        $password = $this->perforce->queryP4Password();
        static::assertEquals('TEST_PASSWORD', $password);
    }

    public function getTestRepoConfig()
    {
        return array(
            'depot' => self::TEST_DEPOT,
            'branch' => self::TEST_BRANCH,
            'p4user' => self::TEST_P4USER,
            'unique_perforce_client_name' => self::TEST_CLIENT_NAME,
            'filename' => 'ASSET.json',
        );
    }

    public function testGetComposerInformationWithoutLabelWithoutStream()
    {
        $expectedCommand = ComposerUtil::getValueByVersion(array(
            '^1.7.0' => 'p4 -u user -c composer_perforce_TEST_depot -p port  print '.escapeshellarg('//depot/ASSET.json'),
            '1.6.*' => 'p4 -u user -c composer_perforce_TEST_depot -p port  print //depot/ASSET.json',
        ));
        $this->processExecutor->expects(static::at(0))
            ->method('execute')
            ->with(static::equalTo($expectedCommand))
            ->willReturnCallback(
                function ($command, &$output) {
                    $output = PerforceTest::getComposerJson();

                    return $command ? true : true;
                }
            )
        ;

        $result = $this->perforce->getComposerInformation('//depot');
        $expected = array(
            'name' => 'test/perforce',
            'description' => 'Basic project for testing',
            'minimum-stability' => 'dev',
            'autoload' => array('psr-0' => array()),
        );
        static::assertEquals($expected, $result);
    }

    public function testGetComposerInformationWithLabelWithoutStream()
    {
        $expectedCommand = ComposerUtil::getValueByVersion(array(
            '^1.7.0' => 'p4 -u user -p port  files '.escapeshellarg('//depot/ASSET.json@0.0.1'),
            '1.6.*' => 'p4 -u user -p port  files //depot/ASSET.json@0.0.1',
        ));
        $this->processExecutor->expects(static::at(0))
            ->method('execute')
            ->with(static::equalTo($expectedCommand))
            ->willReturnCallback(
                function ($command, &$output) {
                    $output = '//depot/ASSET.json#1 - branch change 10001 (text)';

                    return $command ? true : true;
                }
            )
        ;

        $expectedCommand = ComposerUtil::getValueByVersion(array(
            '^1.7.0' => 'p4 -u user -c composer_perforce_TEST_depot -p port  print '.escapeshellarg('//depot/ASSET.json@10001'),
            '1.6.*' => 'p4 -u user -c composer_perforce_TEST_depot -p port  print //depot/ASSET.json@10001',
        ));
        $this->processExecutor->expects(static::at(1))
            ->method('execute')
            ->with(static::equalTo($expectedCommand))
            ->willReturnCallback(
                function ($command, &$output) {
                    $output = PerforceTest::getComposerJson();

                    return $command ? true : true;
                }
            )
        ;

        $result = $this->perforce->getComposerInformation('//depot@0.0.1');

        $expected = array(
            'name' => 'test/perforce',
            'description' => 'Basic project for testing',
            'minimum-stability' => 'dev',
            'autoload' => array('psr-0' => array()),
        );
        static::assertEquals($expected, $result);
    }

    public function testGetComposerInformationWithoutLabelWithStream()
    {
        $this->setAssetPerforceToStream();

        $expectedCommand = ComposerUtil::getValueByVersion(array(
            '^1.7.0' => 'p4 -u user -c composer_perforce_TEST_depot_branch -p port  print '.escapeshellarg('//depot/branch/ASSET.json'),
            '1.6.*' => 'p4 -u user -c composer_perforce_TEST_depot_branch -p port  print //depot/branch/ASSET.json',
        ));
        $this->processExecutor->expects(static::at(0))
            ->method('execute')
            ->with(static::equalTo($expectedCommand))
            ->willReturnCallback(
                function ($command, &$output) {
                    $output = PerforceTest::getComposerJson();

                    return $command ? true : true;
                }
            )
        ;

        $result = $this->perforce->getComposerInformation('//depot/branch');

        $expected = array(
            'name' => 'test/perforce',
            'description' => 'Basic project for testing',
            'minimum-stability' => 'dev',
            'autoload' => array('psr-0' => array()),
        );
        static::assertEquals($expected, $result);
    }

    public function testGetComposerInformationWithLabelWithStream()
    {
        $this->setAssetPerforceToStream();
        $expectedCommand = ComposerUtil::getValueByVersion(array(
            '^1.7.0' => 'p4 -u user -p port  files '.escapeshellarg('//depot/branch/ASSET.json@0.0.1'),
            '1.6.*' => 'p4 -u user -p port  files //depot/branch/ASSET.json@0.0.1',
        ));
        $this->processExecutor->expects(static::at(0))
            ->method('execute')
            ->with(static::equalTo($expectedCommand))
            ->willReturnCallback(
                function ($command, &$output) {
                    $output = '//depot/ASSET.json#1 - branch change 10001 (text)';

                    return $command ? true : true;
                }
            )
        ;

        $expectedCommand = ComposerUtil::getValueByVersion(array(
            '^1.7.0' => 'p4 -u user -c composer_perforce_TEST_depot_branch -p port  print '.escapeshellarg('//depot/branch/ASSET.json@10001'),
            '1.6.*' => 'p4 -u user -c composer_perforce_TEST_depot_branch -p port  print //depot/branch/ASSET.json@10001',
        ));
        $this->processExecutor->expects(static::at(1))
            ->method('execute')
            ->with(static::equalTo($expectedCommand))
            ->willReturnCallback(
                function ($command, &$output) {
                    $output = PerforceTest::getComposerJson();

                    return $command ? true : true;
                }
            )
        ;

        $result = $this->perforce->getComposerInformation('//depot/branch@0.0.1');

        $expected = array(
            'name' => 'test/perforce',
            'description' => 'Basic project for testing',
            'minimum-stability' => 'dev',
            'autoload' => array('psr-0' => array()),
        );
        static::assertEquals($expected, $result);
    }

    public function testGetComposerInformationWithLabelButNoSuchFile()
    {
        $this->setAssetPerforceToStream();
        $expectedCommand = ComposerUtil::getValueByVersion(array(
            '^1.7.0' => 'p4 -u user -p port  files '.escapeshellarg('//depot/branch/ASSET.json@0.0.1'),
            '1.6.*' => 'p4 -u user -p port  files //depot/branch/ASSET.json@0.0.1',
        ));
        $this->processExecutor->expects(static::at(0))
            ->method('execute')
            ->with(static::equalTo($expectedCommand))
            ->willReturnCallback(
                function ($command, &$output) {
                    $output = 'no such file(s).';

                    return $command ? true : true;
                }
            )
        ;

        $result = $this->perforce->getComposerInformation('//depot/branch@0.0.1');

        static::assertNull($result);
    }

    public function testGetComposerInformationWithLabelWithStreamWithNoChange()
    {
        $this->setAssetPerforceToStream();
        $expectedCommand = ComposerUtil::getValueByVersion(array(
            '^1.7.0' => 'p4 -u user -p port  files '.escapeshellarg('//depot/branch/ASSET.json@0.0.1'),
            '1.6.*' => 'p4 -u user -p port  files //depot/branch/ASSET.json@0.0.1',
        ));
        $this->processExecutor->expects(static::at(0))
            ->method('execute')
            ->with(static::equalTo($expectedCommand))
            ->willReturnCallback(
                function ($command, &$output) {
                    $output = '//depot/ASSET.json#1 - branch 10001 (text)';

                    return $command ? true : true;
                }
            )
        ;

        $result = $this->perforce->getComposerInformation('//depot/branch@0.0.1');

        static::assertNull($result);
    }

    public function testCheckServerExists()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessExecutor $processExecutor */
        $processExecutor = $this->getMockBuilder('Composer\Util\ProcessExecutor')->getMock();

        $expectedCommand = ComposerUtil::getValueByVersion(array(
            '^1.7.0' => 'p4 -p '.escapeshellarg('perforce.does.exist:port').' info -s',
            '1.6.*' => 'p4 -p perforce.does.exist:port info -s',
        ));
        $processExecutor->expects(static::at(0))
            ->method('execute')
            ->with(static::equalTo($expectedCommand), static::equalTo(null))
            ->willReturn(0)
        ;

        $result = $this->perforce->checkServerExists('perforce.does.exist:port', $processExecutor);
        static::assertTrue($result);
    }

    /**
     * Test if "p4" command is missing.
     *
     * @covers \Composer\Util\Perforce::checkServerExists
     */
    public function testCheckServerClientError()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessExecutor $processExecutor */
        $processExecutor = $this->getMockBuilder('Composer\Util\ProcessExecutor')->getMock();

        $expectedCommand = ComposerUtil::getValueByVersion(array(
            '^1.7.0' => 'p4 -p '.escapeshellarg('perforce.does.exist:port').' info -s',
            '1.6.*' => 'p4 -p perforce.does.exist:port info -s',
        ));
        $processExecutor->expects(static::at(0))
            ->method('execute')
            ->with(static::equalTo($expectedCommand), static::equalTo(null))
            ->willReturn(127)
        ;

        $result = $this->perforce->checkServerExists('perforce.does.exist:port', $processExecutor);
        static::assertFalse($result);
    }

    public function testCleanupClientSpecShouldDeleteClient()
    {
        /** @var Filesystem|\PHPUnit_Framework_MockObject_MockObject $fs */
        $fs = $this->getMockBuilder('Composer\Util\Filesystem')->getMock();
        $this->perforce->setFilesystem($fs);

        $testClient = $this->perforce->getClient();
        $expectedCommand = ComposerUtil::getValueByVersion(array(
            '^1.7.0' => 'p4 -u '.self::TEST_P4USER.' -p '.self::TEST_PORT.' client -d '.escapeshellarg($testClient),
            '1.6.*' => 'p4 -u '.self::TEST_P4USER.' -p '.self::TEST_PORT.' client -d '.$testClient,
        ));
        $this->processExecutor->expects(static::once())->method('execute')->with(static::equalTo($expectedCommand));

        $fs->expects(static::once())->method('remove')->with($this->perforce->getP4ClientSpec());

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
