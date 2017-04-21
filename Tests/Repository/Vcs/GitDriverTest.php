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

use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Composer\Util\ProcessExecutor;
use Fxp\Composer\AssetPlugin\Repository\AssetRepositoryManager;
use Fxp\Composer\AssetPlugin\Repository\Vcs\GitDriver;

/**
 * Tests of vcs git repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class GitDriverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var AssetRepositoryManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $assetRepositoryManager;

    public function setUp()
    {
        $assetConfig = new \Fxp\Composer\AssetPlugin\Config\Config(array('git-skip-update' => '1 hour'));

        /* @var AssetRepositoryManager|\PHPUnit_Framework_MockObject_MockObject $arm */
        $this->assetRepositoryManager = $this->getMockBuilder(AssetRepositoryManager::class)->disableOriginalConstructor()->getMock();
        $this->assetRepositoryManager->expects($this->any())
            ->method('getConfig')
            ->willReturn($assetConfig);

        $this->config = new Config();
        $this->config->merge(array(
            'config' => array(
                'home' => sys_get_temp_dir().'/composer-test',
                'cache-repo-dir' => sys_get_temp_dir().'/composer-test-cache',
                'cache-vcs-dir' => sys_get_temp_dir().'/composer-test-cache',
            ),
        ));

        // Mock for skip asset
        $fs = new Filesystem();
        $fs->ensureDirectoryExists(sys_get_temp_dir().'/composer-test-cache/https---github.com-fxpio-composer-asset-plugin.git');
        file_put_contents(sys_get_temp_dir().'/composer-test-cache/https---github.com-fxpio-composer-asset-plugin.git/config', '');
    }

    public function tearDown()
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
    public function testPublicRepositoryWithEmptyComposer($type, $filename)
    {
        $repoUrl = 'https://github.com/fxpio/composer-asset-plugin';
        $identifier = 'v0.0.0';
        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
            'asset-repository-manager' => $this->assetRepositoryManager,
        );

        $process = $this->getMockBuilder('Composer\Util\ProcessExecutor')->getMock();
        $process->expects($this->any())
            ->method('splitLines')
            ->will($this->returnValue(array()));
        $process->expects($this->any())
            ->method('execute')
            ->will($this->returnCallback(function () {
                return 0;
            }));

        /* @var IOInterface $io */
        /* @var ProcessExecutor $process */

        $gitDriver = new GitDriver($repoConfig, $io, $this->config, $process, null);
        $gitDriver->initialize();

        $validEmpty = array(
            '_nonexistent_package' => true,
        );

        $this->assertSame($validEmpty, $gitDriver->getComposerInformation($identifier));
    }

    /**
     * @dataProvider getAssetTypes
     *
     * @param string $type
     * @param string $filename
     */
    public function testPublicRepositoryWithSkipUpdate($type, $filename)
    {
        $repoUrl = 'https://github.com/fxpio/composer-asset-plugin.git';
        $identifier = '92bebbfdcde75ef2368317830e54b605bc938123';
        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
            'asset-repository-manager' => $this->assetRepositoryManager,
        );

        $process = $this->getMockBuilder('Composer\Util\ProcessExecutor')->getMock();
        $process->expects($this->any())
            ->method('splitLines')
            ->will($this->returnValue(array()));
        $process->expects($this->any())
            ->method('execute')
            ->will($this->returnCallback(function ($command, &$output = null) use ($identifier, $repoConfig) {
                if ($command === sprintf('git show %s', sprintf('%s:%s', escapeshellarg($identifier), $repoConfig['filename']))) {
                    $output = '{"name": "foo"}';
                } elseif (false !== strpos($command, 'git log')) {
                    $date = new \DateTime(null, new \DateTimeZone('UTC'));
                    $output = $date->getTimestamp();
                }

                return 0;
            }));

        /* @var IOInterface $io */
        /* @var ProcessExecutor $process */

        $gitDriver1 = new GitDriver($repoConfig, $io, $this->config, $process, null);
        $gitDriver1->initialize();

        $gitDriver2 = new GitDriver($repoConfig, $io, $this->config, $process, null);
        $gitDriver2->initialize();

        $validEmpty = array(
            '_nonexistent_package' => true,
        );

        $composer1 = $gitDriver1->getComposerInformation($identifier);
        $composer2 = $gitDriver2->getComposerInformation($identifier);

        $this->assertNotNull($composer1);
        $this->assertNotNull($composer2);
        $this->assertSame($composer1, $composer2);
        $this->assertNotSame($validEmpty, $composer1);
        $this->assertNotSame($validEmpty, $composer2);
    }

    /**
     * @dataProvider getAssetTypes
     *
     * @param string $type
     * @param string $filename
     */
    public function testPublicRepositoryWithCodeCache($type, $filename)
    {
        $repoUrl = 'https://github.com/fxpio/composer-asset-plugin.git';
        $identifier = '92bebbfdcde75ef2368317830e54b605bc938123';
        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
            'asset-repository-manager' => $this->assetRepositoryManager,
        );
        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $process = $this->getMockBuilder('Composer\Util\ProcessExecutor')->getMock();
        $process->expects($this->any())
            ->method('splitLines')
            ->will($this->returnValue(array()));
        $process->expects($this->any())
            ->method('execute')
            ->will($this->returnCallback(function ($command, &$output = null) use ($identifier, $repoConfig) {
                if ($command === sprintf('git show %s', sprintf('%s:%s', escapeshellarg($identifier), $repoConfig['filename']))) {
                    $output = '{"name": "foo"}';
                } elseif (false !== strpos($command, 'git log')) {
                    $date = new \DateTime(null, new \DateTimeZone('UTC'));
                    $output = $date->getTimestamp();
                }

                return 0;
            }));

        /* @var IOInterface $io */
        /* @var ProcessExecutor $process */

        $gitDriver = new GitDriver($repoConfig, $io, $this->config, $process, null);
        $gitDriver->initialize();
        $composer1 = $gitDriver->getComposerInformation($identifier);
        $composer2 = $gitDriver->getComposerInformation($identifier);

        $this->assertNotNull($composer1);
        $this->assertNotNull($composer2);
        $this->assertSame($composer1, $composer2);
    }

    /**
     * @dataProvider getAssetTypes
     *
     * @param string $type
     * @param string $filename
     */
    public function testPublicRepositoryWithFilesystemCache($type, $filename)
    {
        $repoUrl = 'https://github.com/fxpio/composer-asset-plugin.git';
        $identifier = '92bebbfdcde75ef2368317830e54b605bc938123';
        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
            'asset-repository-manager' => $this->assetRepositoryManager,
        );
        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $process = $this->getMockBuilder('Composer\Util\ProcessExecutor')->getMock();
        $process->expects($this->any())
            ->method('splitLines')
            ->will($this->returnValue(array()));
        $process->expects($this->any())
            ->method('execute')
            ->will($this->returnCallback(function ($command, &$output = null) use ($identifier, $repoConfig) {
                if ($command === sprintf('git show %s', sprintf('%s:%s', escapeshellarg($identifier), $repoConfig['filename']))) {
                    $output = '{"name": "foo"}';
                } elseif (false !== strpos($command, 'git log')) {
                    $date = new \DateTime(null, new \DateTimeZone('UTC'));
                    $output = $date->getTimestamp();
                }

                return 0;
            }));

        /* @var IOInterface $io */
        /* @var ProcessExecutor $process */

        $gitDriver1 = new GitDriver($repoConfig, $io, $this->config, $process, null);
        $gitDriver2 = new GitDriver($repoConfig, $io, $this->config, $process, null);
        $gitDriver1->initialize();
        $gitDriver2->initialize();
        $composer1 = $gitDriver1->getComposerInformation($identifier);
        $composer2 = $gitDriver2->getComposerInformation($identifier);

        $this->assertNotNull($composer1);
        $this->assertNotNull($composer2);
        $this->assertSame($composer1, $composer2);
    }

    protected function setAttribute($object, $attribute, $value)
    {
        $attr = new \ReflectionProperty($object, $attribute);
        $attr->setAccessible(true);
        $attr->setValue($object, $value);
    }
}
