<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Repository\Vcs;

use Composer\Cache;
use Composer\Config;
use Composer\Config\ConfigSourceInterface;
use Composer\Downloader\TransportException;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Composer\Util\ProcessExecutor;
use Composer\Util\RemoteFilesystem;
use Fxp\Composer\AssetPlugin\Repository\AssetRepositoryManager;
use Fxp\Composer\AssetPlugin\Repository\Vcs\GitHubDriver;

/**
 * Tests of vcs github repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class GitHubDriverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var AssetRepositoryManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $assetRepositoryManager;

    protected function setUp()
    {
        $this->config = new Config();
        $this->config->merge(array(
            'config' => array(
                'home' => sys_get_temp_dir().'/composer-test',
                'cache-repo-dir' => sys_get_temp_dir().'/composer-test-cache',
            ),
        ));

        $assetConfig = new \Fxp\Composer\AssetPlugin\Config\Config(array());

        $this->assetRepositoryManager = $this->getMockBuilder(AssetRepositoryManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->assetRepositoryManager->expects(static::any())
            ->method('getConfig')
            ->willReturn($assetConfig)
        ;
    }

    protected function tearDown()
    {
        $fs = new Filesystem();
        $fs->removeDirectory(sys_get_temp_dir().'/composer-test');
        $fs->removeDirectory(sys_get_temp_dir().'/composer-test-cache');
    }

    public function getAssetTypes()
    {
        return array(
            array('npm', 'package.json'),
            array('bower', 'bower.json'),
        );
    }

    /**
     * @dataProvider getAssetTypes
     *
     * @param string $type
     * @param string $filename
     */
    public function testPrivateRepository($type, $filename)
    {
        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $repoSshUrl = 'git@github.com:composer-test/repo-name.git';
        $packageName = $type.'-asset/repo-name';
        $identifier = 'v0.0.0';
        $sha = 'SOMESHA';

        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $io->expects(static::any())
            ->method('isInteractive')
            ->willReturn(true)
        ;

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock()
        ;

        $process = $this->getMockBuilder('Composer\Util\ProcessExecutor')->getMock();
        $process->expects(static::any())
            ->method('execute')
            ->willReturn(1)
        ;

        $remoteFilesystem->expects(static::at(0))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo($repoApiUrl), static::equalTo(false))
            ->will(static::throwException(new TransportException('HTTP/1.1 404 Not Found', 404)))
        ;

        $io->expects(static::once())
            ->method('askAndHideAnswer')
            ->with(static::equalTo('Token (hidden): '))
            ->willReturn('sometoken')
        ;

        $io->expects(static::any())
            ->method('setAuthentication')
            ->with(static::equalTo('github.com'), static::matchesRegularExpression('{sometoken|abcdef}'), static::matchesRegularExpression('{x-oauth-basic}'))
        ;

        $remoteFilesystem->expects(static::at(1))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo('https://github.com/composer-test/repo-name'), static::equalTo(false))
            ->willReturn('')
        ;

        $remoteFilesystem->expects(static::at(2))
            ->method('getContents')
            ->willReturn('')
        ;

        $remoteFilesystem->expects(static::at(3))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo($repoApiUrl), static::equalTo(false))
            ->will(static::throwException(new TransportException('HTTP/1.1 404 Not Found', 404)))
        ;

        $remoteFilesystem->expects(static::at(4))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo('https://api.github.com/'), static::equalTo(false))
            ->willReturn('{}')
        ;

        $remoteFilesystem->expects(static::at(5))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo($repoApiUrl), static::equalTo(false))
            ->willReturn($this->createJsonComposer(array('master_branch' => 'test_master', 'private' => true)))
        ;

        $configSource = $this->getMockBuilder('Composer\Config\ConfigSourceInterface')->getMock();
        $authConfigSource = $this->getMockBuilder('Composer\Config\ConfigSourceInterface')->getMock();

        /* @var ConfigSourceInterface $configSource */
        /* @var ConfigSourceInterface $authConfigSource */
        /* @var ProcessExecutor $process */
        /* @var RemoteFilesystem $remoteFilesystem */
        /* @var IOInterface $io */

        $this->config->setConfigSource($configSource);
        $this->config->setAuthConfigSource($authConfigSource);

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'asset-repository-manager' => $this->assetRepositoryManager,
            'filename' => $filename,
            'package-name' => $packageName,
        );

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, $process, $remoteFilesystem);
        $gitHubDriver->initialize();
        $this->setAttribute($gitHubDriver, 'tags', array($identifier => $sha));

        static::assertEquals('test_master', $gitHubDriver->getRootIdentifier());

        $dist = $gitHubDriver->getDist($sha);
        static::assertEquals('zip', $dist['type']);
        static::assertEquals('https://api.github.com/repos/composer-test/repo-name/zipball/SOMESHA', $dist['url']);
        static::assertEquals('SOMESHA', $dist['reference']);

        $source = $gitHubDriver->getSource($sha);
        static::assertEquals('git', $source['type']);
        static::assertEquals($repoSshUrl, $source['url']);
        static::assertEquals('SOMESHA', $source['reference']);
    }

    /**
     * @dataProvider getAssetTypes
     *
     * @param string $type
     * @param string $filename
     */
    public function testPublicRepository($type, $filename)
    {
        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $packageName = $type.'-asset/repo-name';
        $identifier = 'v0.0.0';
        $sha = 'SOMESHA';

        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $io->expects(static::any())
            ->method('isInteractive')
            ->willReturn(true)
        ;

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock()
        ;

        $remoteFilesystem->expects(static::at(0))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo($repoApiUrl), static::equalTo(false))
            ->willReturn($this->createJsonComposer(array('master_branch' => 'test_master')))
        ;

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'asset-repository-manager' => $this->assetRepositoryManager,
            'filename' => $filename,
            'package-name' => $packageName,
        );
        $repoUrl = 'https://github.com/composer-test/repo-name.git';

        /** @var IOInterface $io */
        /** @var RemoteFilesystem $remoteFilesystem */
        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();
        $this->setAttribute($gitHubDriver, 'tags', array($identifier => $sha));

        static::assertEquals('test_master', $gitHubDriver->getRootIdentifier());

        $dist = $gitHubDriver->getDist($sha);
        static::assertEquals('zip', $dist['type']);
        static::assertEquals('https://api.github.com/repos/composer-test/repo-name/zipball/SOMESHA', $dist['url']);
        static::assertEquals($sha, $dist['reference']);

        $source = $gitHubDriver->getSource($sha);
        static::assertEquals('git', $source['type']);
        static::assertEquals($repoUrl, $source['url']);
        static::assertEquals($sha, $source['reference']);
    }

    /**
     * @dataProvider getAssetTypes
     *
     * @param string $type
     * @param string $filename
     */
    public function testPublicRepository2($type, $filename)
    {
        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $packageName = $type.'-asset/repo-name';
        $identifier = 'feature/3.2-foo';
        $sha = 'SOMESHA';

        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $io->expects(static::any())
            ->method('isInteractive')
            ->willReturn(true)
        ;

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock()
        ;

        $remoteFilesystem->expects(static::at(0))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo($repoApiUrl), static::equalTo(false))
            ->willReturn($this->createJsonComposer(array('master_branch' => 'test_master')))
        ;

        $remoteFilesystem->expects(static::at(1))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo('https://api.github.com/repos/composer-test/repo-name/contents/'.$filename.'?ref=feature%2F3.2-foo'), static::equalTo(false))
            ->willReturn('{"encoding":"base64","content":"'.base64_encode('{"support": {"source": "'.$repoUrl.'" }}').'"}')
        ;

        $remoteFilesystem->expects(static::at(2))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo('https://api.github.com/repos/composer-test/repo-name/commits/feature%2F3.2-foo'), static::equalTo(false))
            ->willReturn('{"commit": {"committer":{ "date": "2012-09-10"}}}')
        ;

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'asset-repository-manager' => $this->assetRepositoryManager,
            'filename' => $filename,
            'package-name' => $packageName,
        );
        $repoUrl = 'https://github.com/composer-test/repo-name.git';

        /** @var IOInterface $io */
        /** @var RemoteFilesystem $remoteFilesystem */
        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();
        $this->setAttribute($gitHubDriver, 'tags', array($identifier => $sha));

        static::assertEquals('test_master', $gitHubDriver->getRootIdentifier());

        $dist = $gitHubDriver->getDist($sha);
        static::assertEquals('zip', $dist['type']);
        static::assertEquals('https://api.github.com/repos/composer-test/repo-name/zipball/SOMESHA', $dist['url']);
        static::assertEquals($sha, $dist['reference']);

        $source = $gitHubDriver->getSource($sha);
        static::assertEquals('git', $source['type']);
        static::assertEquals($repoUrl, $source['url']);
        static::assertEquals($sha, $source['reference']);

        $gitHubDriver->getComposerInformation($identifier);
    }

    /**
     * @dataProvider getAssetTypes
     *
     * @param string $type
     * @param string $filename
     */
    public function testPrivateRepositoryNoInteraction($type, $filename)
    {
        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $repoSshUrl = 'git@github.com:composer-test/repo-name.git';
        $packageName = $type.'-asset/repo-name';
        $identifier = 'v0.0.0';
        $sha = 'SOMESHA';

        $process = $this->getMockBuilder('Composer\Util\ProcessExecutor')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $io->expects(static::any())
            ->method('isInteractive')
            ->willReturn(false)
        ;

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock()
        ;

        $remoteFilesystem->expects(static::at(0))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo($repoApiUrl), static::equalTo(false))
            ->will(static::throwException(new TransportException('HTTP/1.1 404 Not Found', 404)))
        ;

        $remoteFilesystem->expects(static::at(1))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo('https://github.com/composer-test/repo-name'), static::equalTo(false))
            ->willReturn('')
        ;

        $remoteFilesystem->expects(static::at(2))
            ->method('getContents')
            ->willReturn('')
        ;

        $remoteFilesystem->expects(static::at(3))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo($repoApiUrl), static::equalTo(false))
            ->will(static::throwException(new TransportException('HTTP/1.1 404 Not Found', 404)))
        ;

        // clean local clone if present
        $fs = new Filesystem();
        $fs->removeDirectory(sys_get_temp_dir().'/composer-test');

        $process->expects(static::at(0))
            ->method('execute')
            ->with(static::equalTo('git config github.accesstoken'))
            ->willReturn(1)
        ;

        $process->expects(static::at(1))
            ->method('execute')
            ->with(static::stringContains($repoSshUrl))
            ->willReturn(0)
        ;

        $process->expects(static::at(2))
            ->method('execute')
            ->with(static::stringContains('git show-ref --tags'))
        ;

        $process->expects(static::at(3))
            ->method('splitLines')
            ->willReturn(array($sha.' refs/tags/'.$identifier))
        ;

        $process->expects(static::at(4))
            ->method('execute')
            ->with(static::stringContains('git branch --no-color --no-abbrev -v'))
        ;

        $process->expects(static::at(5))
            ->method('splitLines')
            ->willReturn(array('  test_master     edf93f1fccaebd8764383dc12016d0a1a9672d89 Fix test & behavior'))
        ;

        $process->expects(static::at(6))
            ->method('execute')
            ->with(static::stringContains('git branch --no-color'))
        ;

        $process->expects(static::at(7))
            ->method('splitLines')
            ->willReturn(array('* test_master'))
        ;

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'asset-repository-manager' => $this->assetRepositoryManager,
            'filename' => $filename,
            'package-name' => $packageName,
        );

        /** @var IOInterface $io */
        /** @var RemoteFilesystem $remoteFilesystem */
        /** @var ProcessExecutor $process */
        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, $process, $remoteFilesystem);
        $gitHubDriver->initialize();

        static::assertEquals('test_master', $gitHubDriver->getRootIdentifier());

        $dist = $gitHubDriver->getDist($sha);
        static::assertEquals('zip', $dist['type']);
        static::assertEquals('https://api.github.com/repos/composer-test/repo-name/zipball/SOMESHA', $dist['url']);
        static::assertEquals($sha, $dist['reference']);

        $source = $gitHubDriver->getSource($identifier);
        static::assertEquals('git', $source['type']);
        static::assertEquals($repoSshUrl, $source['url']);
        static::assertEquals($identifier, $source['reference']);

        $source = $gitHubDriver->getSource($sha);
        static::assertEquals('git', $source['type']);
        static::assertEquals($repoSshUrl, $source['url']);
        static::assertEquals($sha, $source['reference']);
    }

    /**
     * @dataProvider getAssetTypes
     *
     * @param string $type
     * @param string $filename
     */
    public function testGetComposerInformationWithGitDriver($type, $filename)
    {
        $repoUrl = 'https://github.com/composer-test/repo-name';
        $identifier = 'v0.0.0';

        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $io->expects(static::any())
            ->method('isInteractive')
            ->willReturn(true)
        ;

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'asset-repository-manager' => $this->assetRepositoryManager,
            'filename' => $filename,
            'no-api' => true,
        );

        $process = $this->getMockBuilder('Composer\Util\ProcessExecutor')->getMock();
        $process->expects(static::any())
            ->method('splitLines')
            ->willReturn(array())
        ;
        $process->expects(static::any())
            ->method('execute')
            ->willReturnCallback(function () {
                return 0;
            })
        ;

        /** @var IOInterface $io */
        /** @var ProcessExecutor $process */
        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, $process, null);
        $gitHubDriver->initialize();

        $validEmpty = array(
            '_nonexistent_package' => true,
        );

        static::assertSame($validEmpty, $gitHubDriver->getComposerInformation($identifier));
    }

    /**
     * @dataProvider getAssetTypes
     *
     * @param string $type
     * @param string $filename
     */
    public function testGetComposerInformationWithCodeCache($type, $filename)
    {
        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $packageName = $type.'-asset/repo-name';
        $identifier = 'dev-master';
        $sha = '92bebbfdcde75ef2368317830e54b605bc938123';

        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $io->expects(static::any())
            ->method('isInteractive')
            ->willReturn(true)
        ;

        /** @var IOInterface $io */
        /** @var RemoteFilesystem $remoteFilesystem */
        $remoteFilesystem = $this->createMockRremoteFilesystem($io, $repoApiUrl, $filename, $sha, false);
        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'asset-repository-manager' => $this->assetRepositoryManager,
            'filename' => $filename,
            'package-name' => $packageName,
        );

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();
        $this->setAttribute($gitHubDriver, 'tags', array($identifier => $sha));
        $this->setAttribute($gitHubDriver, 'hasIssues', true);

        $composer1 = $gitHubDriver->getComposerInformation($sha);
        $composer2 = $gitHubDriver->getComposerInformation($sha);

        static::assertSame($composer1, $composer2);
    }

    /**
     * @dataProvider getAssetTypes
     *
     * @param string $type
     * @param string $filename
     */
    public function testGetComposerInformationWithFilesystemCache($type, $filename)
    {
        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $packageName = $type.'-asset/repo-name';
        $identifier = 'dev-master';
        $sha = '92bebbfdcde75ef2368317830e54b605bc938123';

        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $io->expects(static::any())
            ->method('isInteractive')
            ->willReturn(true)
        ;

        /** @var IOInterface $io */
        /** @var RemoteFilesystem $remoteFilesystem1 */
        $remoteFilesystem1 = $this->createMockRremoteFilesystem($io, $repoApiUrl, $filename, $sha, false);
        /** @var RemoteFilesystem $remoteFilesystem2 */
        $remoteFilesystem2 = $this->createMockRremoteFilesystem($io, $repoApiUrl, $filename, $sha, true);
        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'asset-repository-manager' => $this->assetRepositoryManager,
            'filename' => $filename,
            'package-name' => $packageName,
        );

        $gitHubDriver1 = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem1);
        $gitHubDriver2 = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem2);
        $gitHubDriver1->initialize();
        $gitHubDriver2->initialize();
        $this->setAttribute($gitHubDriver1, 'tags', array($identifier => $sha));
        $this->setAttribute($gitHubDriver1, 'hasIssues', true);
        $this->setAttribute($gitHubDriver2, 'tags', array($identifier => $sha));
        $this->setAttribute($gitHubDriver2, 'hasIssues', true);

        $composer1 = $gitHubDriver1->getComposerInformation($sha);
        $composer2 = $gitHubDriver2->getComposerInformation($sha);

        static::assertSame($composer1, $composer2);
    }

    /**
     * @dataProvider getAssetTypes
     *
     * @param string $type
     * @param string $filename
     */
    public function testGetComposerInformationWithEmptyContent($type, $filename)
    {
        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $packageName = $type.'-asset/repo-name';
        $identifier = 'v0.0.0';

        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock()
        ;

        $remoteFilesystem->expects(static::at(0))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo($repoApiUrl), static::equalTo(false))
            ->willReturn($this->createJsonComposer(array('master_branch' => 'test_master')))
        ;

        $remoteFilesystem->expects(static::at(1))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo('https://api.github.com/repos/composer-test/repo-name/contents/'.$filename.'?ref='.$identifier), static::equalTo(false))
            ->will(static::throwException(new TransportException('Not Found', 404)))
        ;
        $remoteFilesystem->expects(static::at(2))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo('https://api.github.com/repos/composer-test/repo-name/contents/'.$filename.'?ref='.$identifier), static::equalTo(false))
            ->will(static::throwException(new TransportException('Not Found', 404)))
        ;

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'asset-repository-manager' => $this->assetRepositoryManager,
            'filename' => $filename,
            'package-name' => $packageName,
        );

        /** @var IOInterface $io */
        /** @var RemoteFilesystem $remoteFilesystem */
        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();

        $validEmpty = array(
            '_nonexistent_package' => true,
        );

        static::assertSame($validEmpty, $gitHubDriver->getComposerInformation($identifier));
    }

    /**
     * @dataProvider getAssetTypes
     *
     * @param string $type
     * @param string $filename
     *
     * @expectedException \RuntimeException
     */
    public function testGetComposerInformationWithRuntimeException($type, $filename)
    {
        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $packageName = $type.'-asset/repo-name';
        $identifier = 'v0.0.0';

        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock()
        ;

        $remoteFilesystem->expects(static::at(0))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo($repoApiUrl), static::equalTo(false))
            ->willReturn($this->createJsonComposer(array('master_branch' => 'test_master')))
        ;

        $remoteFilesystem->expects(static::at(1))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo('https://api.github.com/repos/composer-test/repo-name/contents/'.$filename.'?ref='.$identifier), static::equalTo(false))
            ->willReturn('{"encoding":"base64","content":""}')
        ;

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'asset-repository-manager' => $this->assetRepositoryManager,
            'filename' => $filename,
            'package-name' => $packageName,
        );

        /** @var IOInterface $io */
        /** @var RemoteFilesystem $remoteFilesystem */
        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();

        $gitHubDriver->getComposerInformation($identifier);
    }

    /**
     * @dataProvider getAssetTypes
     *
     * @param string $type
     * @param string $filename
     *
     * @expectedException \RuntimeException
     */
    public function testGetComposerInformationWithTransportException($type, $filename)
    {
        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $packageName = $type.'-asset/repo-name';
        $identifier = 'v0.0.0';

        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock()
        ;

        $remoteFilesystem->expects(static::at(0))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo($repoApiUrl), static::equalTo(false))
            ->willReturn($this->createJsonComposer(array('master_branch' => 'test_master')))
        ;

        $remoteFilesystem->expects(static::at(1))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo('https://api.github.com/repos/composer-test/repo-name/contents/'.$filename.'?ref='.$identifier), static::equalTo(false))
            ->will(static::throwException(new TransportException('Mock exception code 404', 404)))
        ;

        $remoteFilesystem->expects(static::at(2))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo('https://api.github.com/repos/composer-test/repo-name/contents/'.$filename.'?ref='.$identifier), static::equalTo(false))
            ->will(static::throwException(new TransportException('Mock exception code 400', 400)))
        ;

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'asset-repository-manager' => $this->assetRepositoryManager,
            'filename' => $filename,
            'package-name' => $packageName,
        );

        /** @var IOInterface $io */
        /** @var RemoteFilesystem $remoteFilesystem */
        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();

        $gitHubDriver->getComposerInformation($identifier);
    }

    /**
     * @dataProvider getAssetTypes
     *
     * @param string $type
     * @param string $filename
     */
    public function testRedirectUrlRepository($type, $filename)
    {
        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $packageName = $type.'-asset/repo-name';
        $identifier = 'v0.0.0';
        $sha = 'SOMESHA';

        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $io->expects(static::any())
            ->method('isInteractive')
            ->willReturn(true)
        ;

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock()
        ;

        $remoteFilesystem->expects(static::at(0))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo($repoApiUrl), static::equalTo(false))
            ->will(static::throwException(new TransportException('HTTP/1.1 404 Not Found', 404)))
        ;

        $remoteFilesystem->expects(static::at(1))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo('https://github.com/composer-test/repo-name'), static::equalTo(false))
            ->willReturn('')
        ;

        $remoteFilesystem->expects(static::at(2))
            ->method('getLastHeaders')
            ->willReturn(array(
                'HTTP/1.1 301 Moved Permanently',
                'Header-parameter: test',
                'Location: '.$repoUrl.'-new',
            ))
        ;

        $remoteFilesystem->expects(static::at(3))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo($repoApiUrl.'-new'), static::equalTo(false))
            ->willReturn($this->createJsonComposer(array('master_branch' => 'test_master')))
        ;

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'asset-repository-manager' => $this->assetRepositoryManager,
            'filename' => $filename,
            'package-name' => $packageName,
        );
        $repoUrl = 'https://github.com/composer-test/repo-name.git';

        /** @var IOInterface $io */
        /** @var RemoteFilesystem $remoteFilesystem */
        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();
        $this->setAttribute($gitHubDriver, 'tags', array($identifier => $sha));

        static::assertEquals('test_master', $gitHubDriver->getRootIdentifier());

        $dist = $gitHubDriver->getDist($sha);
        static::assertEquals('zip', $dist['type']);
        static::assertEquals('https://api.github.com/repos/composer-test/repo-name/zipball/SOMESHA', $dist['url']);
        static::assertEquals($sha, $dist['reference']);

        $source = $gitHubDriver->getSource($sha);
        static::assertEquals('git', $source['type']);
        static::assertEquals($repoUrl, $source['url']);
        static::assertEquals($sha, $source['reference']);
    }

    /**
     * @dataProvider getAssetTypes
     *
     * @param string $type
     * @param string $filename
     *
     * @expectedException \RuntimeException
     */
    public function testRedirectUrlWithNonexistentRepository($type, $filename)
    {
        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $packageName = $type.'-asset/repo-name';
        $identifier = 'v0.0.0';

        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $io->expects(static::any())
            ->method('isInteractive')
            ->willReturn(true)
        ;

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock()
        ;

        $remoteFilesystem->expects(static::at(0))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo($repoApiUrl), static::equalTo(false))
            ->will(static::throwException(new TransportException('HTTP/1.1 404 Not Found', 404)))
        ;

        $io->expects(static::once())
            ->method('askAndHideAnswer')
            ->with(static::equalTo('Token (hidden): '))
            ->willReturn('sometoken')
        ;

        $io->expects(static::any())
            ->method('setAuthentication')
            ->with(static::equalTo('github.com'), static::matchesRegularExpression('{sometoken|abcdef}'), static::matchesRegularExpression('{x-oauth-basic}'))
        ;

        $remoteFilesystem->expects(static::at(1))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo('https://github.com/composer-test/repo-name'), static::equalTo(false))
            ->will(static::throwException(new TransportException('HTTP/1.1 404 Not Found', 404)))
        ;

        $remoteFilesystem->expects(static::at(2))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo($repoApiUrl), static::equalTo(false))
            ->will(static::throwException(new TransportException('HTTP/1.1 404 Not Found', 404)))
        ;

        $remoteFilesystem->expects(static::at(3))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo('https://api.github.com/'), static::equalTo(false))
            ->willReturn('{}')
        ;

        $remoteFilesystem->expects(static::at(4))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo($repoApiUrl), static::equalTo(false))
            ->will(static::throwException(new TransportException('HTTP/1.1 404 Not Found', 404)))
        ;

        $remoteFilesystem->expects(static::at(5))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo($repoApiUrl.'/contents/'.$filename.'?ref='.$identifier), static::equalTo(false))
            ->will(static::throwException(new TransportException('HTTP/1.1 404 Not Found', 404)))
        ;

        $configSource = $this->getMockBuilder('Composer\Config\ConfigSourceInterface')->getMock();
        $authConfigSource = $this->getMockBuilder('Composer\Config\ConfigSourceInterface')->getMock();

        /* @var ConfigSourceInterface $configSource */
        /* @var ConfigSourceInterface $authConfigSource */

        $this->config->setConfigSource($configSource);
        $this->config->setAuthConfigSource($authConfigSource);

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'asset-repository-manager' => $this->assetRepositoryManager,
            'filename' => $filename,
            'package-name' => $packageName,
        );

        /** @var IOInterface $io */
        /** @var RemoteFilesystem $remoteFilesystem */
        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $firstNonexistent = false;

        try {
            $gitHubDriver->initialize();
        } catch (TransportException $e) {
            $firstNonexistent = true;
        }

        static::assertTrue($firstNonexistent);

        $gitHubDriver->getComposerInformation($identifier);
    }

    /**
     * @dataProvider getAssetTypes
     *
     * @param string $type
     * @param string $filename
     */
    public function testRedirectUrlRepositoryWithCache($type, $filename)
    {
        $originUrl = 'github.com';
        $owner = 'composer-test';
        $repository = 'repo-name';
        $repoUrl = 'http://'.$originUrl.'/'.$owner.'/'.$repository;
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $repoApiUrlNew = $repoApiUrl.'-new';
        $packageName = $type.'-asset/repo-name';
        $identifier = 'v0.0.0';
        $sha = 'SOMESHA';

        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $io->expects(static::any())
            ->method('isInteractive')
            ->willReturn(true)
        ;

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock()
        ;

        $remoteFilesystem->expects(static::at(0))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo($repoApiUrlNew), static::equalTo(false))
            ->willReturn($this->createJsonComposer(array('master_branch' => 'test_master')))
        ;

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'asset-repository-manager' => $this->assetRepositoryManager,
            'filename' => $filename,
            'package-name' => $packageName,
        );
        $repoUrl = 'https://github.com/composer-test/repo-name.git';

        /** @var IOInterface $io */
        /** @var RemoteFilesystem $remoteFilesystem */
        $cache = new Cache($io, $this->config->get('cache-repo-dir').'/'.$originUrl.'/'.$owner.'/'.$repository);
        $cache->write('redirect-api', $repoApiUrlNew);

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();
        $this->setAttribute($gitHubDriver, 'tags', array($identifier => $sha));

        static::assertEquals('test_master', $gitHubDriver->getRootIdentifier());

        $dist = $gitHubDriver->getDist($sha);
        static::assertEquals('zip', $dist['type']);
        static::assertEquals('https://api.github.com/repos/composer-test/repo-name/zipball/SOMESHA', $dist['url']);
        static::assertEquals($sha, $dist['reference']);

        $source = $gitHubDriver->getSource($sha);
        static::assertEquals('git', $source['type']);
        static::assertEquals($repoUrl, $source['url']);
        static::assertEquals($sha, $source['reference']);
    }

    public function getDataBranches()
    {
        $valid1 = array();
        $git1 = array();
        $valid2 = array(
            'master' => '0123456789abcdef0123456789abcdef01234567',
        );
        $git2 = array(
            'master 0123456789abcdef0123456789abcdef01234567 Comment',
        );
        $valid3 = array(
            'gh-pages' => '0123456789abcdef0123456789abcdef01234567',
        );
        $git3 = array(
            'gh-pages 0123456789abcdef0123456789abcdef01234567 Comment',
        );
        $valid4 = array(
            'master' => '0123456789abcdef0123456789abcdef01234567',
            'gh-pages' => '0123456789abcdef0123456789abcdef01234567',
        );
        $git4 = array(
            'master 0123456789abcdef0123456789abcdef01234567 Comment',
            'gh-pages 0123456789abcdef0123456789abcdef01234567 Comment',
        );

        return array(
            array('npm', 'package.json', $valid1, $git1),
            array('npm', 'package.json', $valid2, $git2),
            array('npm', 'package.json', $valid3, $git3),
            array('npm', 'package.json', $valid4, $git4),
            array('bower', 'bower.json', $valid1, $git1),
            array('bower', 'bower.json', $valid2, $git2),
            array('bower', 'bower.json', $valid3, $git3),
            array('bower', 'bower.json', $valid4, $git4),
        );
    }

    /**
     * @dataProvider getDataBranches
     *
     * @param string $type
     * @param string $filename
     * @param array  $branches
     * @param array  $gitBranches
     */
    public function testGetBranchesWithGitDriver($type, $filename, array $branches, array $gitBranches)
    {
        $repoUrl = 'https://github.com/composer-test/repo-name';

        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $io->expects(static::any())
            ->method('isInteractive')
            ->willReturn(true)
        ;

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'asset-repository-manager' => $this->assetRepositoryManager,
            'filename' => $filename,
            'no-api' => true,
        );

        $process = $this->getMockBuilder('Composer\Util\ProcessExecutor')->getMock();
        $process->expects(static::any())
            ->method('splitLines')
            ->willReturn($gitBranches)
        ;
        $process->expects(static::any())
            ->method('execute')
            ->willReturnCallback(function () {
                return 0;
            })
        ;

        /** @var IOInterface $io */
        /** @var ProcessExecutor $process */
        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, $process, null);
        $gitHubDriver->initialize();

        static::assertSame($branches, $gitHubDriver->getBranches());
    }

    /**
     * @dataProvider getDataBranches
     *
     * @param string $type
     * @param string $filename
     * @param array  $branches
     */
    public function testGetBranches($type, $filename, array $branches)
    {
        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $packageName = $type.'-asset/repo-name';
        $identifier = 'v0.0.0';
        $sha = 'SOMESHA';

        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $io->expects(static::any())
            ->method('isInteractive')
            ->willReturn(true)
        ;

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock()
        ;

        $remoteFilesystem->expects(static::at(0))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo($repoApiUrl), static::equalTo(false))
            ->willReturn($this->createJsonComposer(array('master_branch' => 'gh-pages')))
        ;

        $remoteFilesystem->expects(static::any())
            ->method('getLastHeaders')
            ->willReturn(array())
        ;

        $githubBranches = array();
        foreach ($branches as $branch => $sha) {
            $githubBranches[] = array(
                'ref' => 'refs/heads/'.$branch,
                'object' => array(
                    'sha' => $sha,
                ),
            );
        }

        $remoteFilesystem->expects(static::at(1))
            ->method('getContents')
            ->willReturn(json_encode($githubBranches))
        ;

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'asset-repository-manager' => $this->assetRepositoryManager,
            'filename' => $filename,
            'package-name' => $packageName,
        );

        /** @var IOInterface $io */
        /** @var RemoteFilesystem $remoteFilesystem */
        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();
        $this->setAttribute($gitHubDriver, 'tags', array($identifier => $sha));

        static::assertEquals('gh-pages', $gitHubDriver->getRootIdentifier());
        static::assertSame($branches, $gitHubDriver->getBranches());
    }

    /**
     * @dataProvider getDataBranches
     *
     * @param string $type
     * @param string $filename
     * @param array  $branches
     * @param array  $gitBranches
     */
    public function testNoApi($type, $filename, array $branches, array $gitBranches)
    {
        $repoUrl = 'https://github.com/composer-test/repo-name';
        $packageName = $type.'-asset/repo-name';

        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $io->expects(static::any())
            ->method('isInteractive')
            ->willReturn(true)
        ;

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'asset-repository-manager' => $this->assetRepositoryManager,
            'filename' => $filename,
            'package-name' => $packageName,
            'vcs-driver-options' => array(
                'github-no-api' => true,
            ),
        );

        $process = $this->getMockBuilder('Composer\Util\ProcessExecutor')->getMock();
        $process->expects(static::any())
            ->method('splitLines')
            ->willReturn($gitBranches)
        ;
        $process->expects(static::any())
            ->method('execute')
            ->willReturnCallback(function () {
                return 0;
            })
        ;

        /** @var IOInterface $io */
        /** @var ProcessExecutor $process */
        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, $process, null);
        $gitHubDriver->initialize();

        static::assertSame($branches, $gitHubDriver->getBranches());
    }

    /**
     * @param object $object
     * @param string $attribute
     * @param mixed  $value
     */
    protected function setAttribute($object, $attribute, $value)
    {
        $attr = new \ReflectionProperty($object, $attribute);
        $attr->setAccessible(true);
        $attr->setValue($object, $value);
    }

    /**
     * Creates the json composer content.
     *
     * @param array  $content The composer content
     * @param string $name    The name of repository
     * @param string $login   The username /organization of repository
     *
     * @return string The json content
     */
    protected function createJsonComposer(array $content, $name = 'repo-name', $login = 'composer-test')
    {
        return json_encode(array_merge_recursive($content, array(
            'name' => $name,
            'owner' => array(
                'login' => $login,
            ),
        )));
    }

    /**
     * @param IOInterface $io
     * @param string      $repoApiUrl
     * @param string      $filename
     * @param string      $sha
     * @param bool        $forCache
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createMockRremoteFilesystem($io, $repoApiUrl, $filename, $sha, $forCache)
    {
        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock()
        ;

        $remoteFilesystem->expects(static::at(0))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo($repoApiUrl), static::equalTo(false))
            ->willReturn($this->createJsonComposer(array('master_branch' => 'test_master')))
        ;

        if ($forCache) {
            return $remoteFilesystem;
        }

        $remoteFilesystem->expects(static::at(1))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo('https://api.github.com/repos/composer-test/repo-name/contents/'.$filename.'?ref='.$sha), static::equalTo(false))
            ->willReturn('{"encoding":"base64","content":"'.base64_encode('{"support": {}}').'"}')
        ;

        $remoteFilesystem->expects(static::at(2))
            ->method('getContents')
            ->with(static::equalTo('github.com'), static::equalTo('https://api.github.com/repos/composer-test/repo-name/commits/'.$sha), static::equalTo(false))
            ->willReturn('{"commit": {"committer":{ "date": "2012-09-10"}}}')
        ;

        return $remoteFilesystem;
    }
}
