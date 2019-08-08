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
use Fxp\Composer\AssetPlugin\Repository\Vcs\SvnDriver;

/**
 * Tests of vcs svn repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class SvnDriverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Config
     */
    private $config;

    protected function setUp()
    {
        $this->config = new Config();
        $this->config->merge(array(
            'config' => array(
                'home' => sys_get_temp_dir().'/composer-test',
                'cache-repo-dir' => sys_get_temp_dir().'/composer-test-cache',
                'secure-http' => false,
            ),
        ));
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
            array('npm', 'package.json', '1234'),
            array('npm', 'package.json', '/@1234'),
            array('bower', 'bower.json', '1234'),
            array('bower', 'bower.json', '/@1234'),
        );
    }

    /**
     * @dataProvider getAssetTypes
     *
     * @param string $type
     * @param string $filename
     * @param string $identifier
     */
    public function testPublicRepositoryWithEmptyComposer($type, $filename, $identifier)
    {
        $repoUrl = 'svn://example.tld/composer-test/repo-name/trunk';
        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
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
        $driver = new SvnDriver($repoConfig, $io, $this->config, $process, null);
        $driver->initialize();

        $validEmpty = array(
            '_nonexistent_package' => true,
        );

        static::assertSame($validEmpty, $driver->getComposerInformation($identifier));
    }

    /**
     * @dataProvider getAssetTypes
     *
     * @param string $type
     * @param string $filename
     * @param string $identifier
     */
    public function testPrivateRepositoryWithEmptyComposer($type, $filename, $identifier)
    {
        $this->config->merge(array(
            'config' => array(
                'http-basic' => array(
                    'example.tld' => array(
                        'username' => 'peter',
                        'password' => 'quill',
                    ),
                ),
            ),
        ));

        $repoBaseUrl = 'svn://example.tld/composer-test/repo-name';
        $repoUrl = $repoBaseUrl.'/trunk';
        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
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
        $driver = new SvnDriver($repoConfig, $io, $this->config, $process, null);
        $driver->initialize();

        $validEmpty = array(
            '_nonexistent_package' => true,
        );

        static::assertSame($validEmpty, $driver->getComposerInformation($identifier));
    }

    /**
     * @dataProvider getAssetTypes
     *
     * @param string $type
     * @param string $filename
     * @param string $identifier
     */
    public function testPublicRepositoryWithCodeCache($type, $filename, $identifier)
    {
        $repoBaseUrl = 'svn://example.tld/composer-test/repo-name';
        $repoUrl = $repoBaseUrl.'/trunk';
        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
        );
        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $process = $this->getMockBuilder('Composer\Util\ProcessExecutor')->getMock();
        $process->expects(static::any())
            ->method('splitLines')
            ->willReturnCallback(function ($value) {
                return \is_string($value) ? preg_split('{\r?\n}', $value) : array();
            })
        ;
        $process->expects(static::any())
            ->method('execute')
            ->willReturnCallback(function ($command, &$output) use ($repoBaseUrl, $identifier, $repoConfig) {
                if ($command === sprintf('svn cat --non-interactive  %s', ProcessExecutor::escape(sprintf('%s/%s/%s', $repoBaseUrl, $identifier, $repoConfig['filename'])))
                        || $command === sprintf('svn cat --non-interactive  %s', ProcessExecutor::escape(sprintf('%s/%s%s', $repoBaseUrl, $repoConfig['filename'], trim($identifier, '/'))))) {
                    $output('out', '{"name": "foo"}');
                } elseif ($command === sprintf('svn info --non-interactive  %s', ProcessExecutor::escape(sprintf('%s/%s/', $repoBaseUrl, $identifier)))
                        || $command === sprintf('svn info --non-interactive  %s', ProcessExecutor::escape(sprintf('%s/%s', $repoBaseUrl, trim($identifier, '/'))))) {
                    $date = new \DateTime(null, new \DateTimeZone('UTC'));
                    $value = array(
                        'Last Changed Rev: '.$identifier,
                        'Last Changed Date: '.$date->format('Y-m-d H:i:s O').' ('.$date->format('l, j F Y').')',
                    );

                    $output('out', implode(PHP_EOL, $value));
                }

                return 0;
            })
        ;

        /** @var IOInterface $io */
        /** @var ProcessExecutor $process */
        $driver = new SvnDriver($repoConfig, $io, $this->config, $process, null);
        $driver->initialize();
        $composer1 = $driver->getComposerInformation($identifier);
        $composer2 = $driver->getComposerInformation($identifier);

        static::assertNotNull($composer1);
        static::assertNotNull($composer2);
        static::assertSame($composer1, $composer2);
        static::assertArrayHasKey('time', $composer1);
    }

    /**
     * @dataProvider getAssetTypes
     *
     * @param string $type
     * @param string $filename
     * @param string $identifier
     */
    public function testPublicRepositoryWithFilesystemCache($type, $filename, $identifier)
    {
        $repoBaseUrl = 'svn://example.tld/composer-test/repo-name';
        $repoUrl = $repoBaseUrl.'/trunk';
        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
        );
        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();
        $process = $this->getMockBuilder('Composer\Util\ProcessExecutor')->getMock();
        $process->expects(static::any())
            ->method('splitLines')
            ->willReturnCallback(function ($value) {
                return \is_string($value) ? preg_split('{\r?\n}', $value) : array();
            })
        ;
        $process->expects(static::any())
            ->method('execute')
            ->willReturnCallback(function ($command, &$output) use ($repoBaseUrl, $identifier, $repoConfig) {
                if ($command === sprintf('svn cat --non-interactive  %s', ProcessExecutor::escape(sprintf('%s/%s/%s', $repoBaseUrl, $identifier, $repoConfig['filename'])))
                        || $command === sprintf('svn cat --non-interactive  %s', ProcessExecutor::escape(sprintf('%s/%s%s', $repoBaseUrl, $repoConfig['filename'], trim($identifier, '/'))))) {
                    $output('out', '{"name": "foo"}');
                } elseif ($command === sprintf('svn info --non-interactive  %s', ProcessExecutor::escape(sprintf('%s/%s/', $repoBaseUrl, $identifier)))
                        || $command === sprintf('svn info --non-interactive  %s', ProcessExecutor::escape(sprintf('%s/%s', $repoBaseUrl, trim($identifier, '/'))))) {
                    $date = new \DateTime(null, new \DateTimeZone('UTC'));
                    $value = array(
                        'Last Changed Rev: '.$identifier,
                        'Last Changed Date: '.$date->format('Y-m-d H:i:s O').' ('.$date->format('l, j F Y').')',
                    );

                    $output('out', implode(PHP_EOL, $value));
                }

                return 0;
            })
        ;

        /** @var IOInterface $io */
        /** @var ProcessExecutor $process */
        $driver1 = new SvnDriver($repoConfig, $io, $this->config, $process, null);
        $driver2 = new SvnDriver($repoConfig, $io, $this->config, $process, null);
        $driver1->initialize();
        $driver2->initialize();
        $composer1 = $driver1->getComposerInformation($identifier);
        $composer2 = $driver2->getComposerInformation($identifier);

        static::assertNotNull($composer1);
        static::assertNotNull($composer2);
        static::assertSame($composer1, $composer2);
        static::assertArrayHasKey('time', $composer1);
    }

    /**
     * @dataProvider getAssetTypes
     *
     * @param string $type
     * @param string $filename
     * @param string $identifier
     *
     * @expectedException \Composer\Downloader\TransportException
     */
    public function testPublicRepositoryWithInvalidUrl($type, $filename, $identifier)
    {
        $repoUrl = 'svn://example.tld/composer-test/repo-name/trunk';
        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
        );

        $process = $this->getMockBuilder('Composer\Util\ProcessExecutor')->getMock();
        $process->expects(static::any())
            ->method('splitLines')
            ->willReturn(array())
        ;
        $process->expects(static::any())
            ->method('execute')
            ->willReturnCallback(function ($command) {
                return 0 === strpos($command, 'svn cat ') ? 1 : 0;
            })
        ;

        /** @var IOInterface $io */
        /** @var ProcessExecutor $process */
        $driver = new SvnDriver($repoConfig, $io, $this->config, $process, null);
        $driver->initialize();
        $driver->getComposerInformation($identifier);
    }

    /**
     * @return array
     */
    public function getSupportsUrls()
    {
        return array(
            array('svn://example.tld/trunk',           true,  'svn://example.tld/trunk'),
            array('svn+ssh://example.tld/trunk',       true,  'svn+ssh://example.tld/trunk'),
            array('svn://svn.example.tld/trunk',       true,  'svn://svn.example.tld/trunk'),
            array('svn+ssh://svn.example.tld/trunk',   true,  'svn+ssh://svn.example.tld/trunk'),
            array('svn+http://svn.example.tld/trunk',  true,  'http://svn.example.tld/trunk'),
            array('svn+https://svn.example.tld/trunk', true,  'https://svn.example.tld/trunk'),
            array('http://example.tld/svn/trunk',      true,  'http://example.tld/svn/trunk'),
            array('https://example.tld/svn/trunk',     true,  'https://example.tld/svn/trunk'),
            array('http://example.tld/sub',            false, null),
            array('https://example.tld/sub',           false, null),
        );
    }

    /**
     * @dataProvider getSupportsUrls
     *
     * @param string $url
     * @param string $supperted
     * @param string $urlUsed
     */
    public function testSupports($url, $supperted, $urlUsed)
    {
        /** @var IOInterface $io */
        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();

        static::assertSame($supperted, SvnDriver::supports($io, $this->config, $url, false));

        if (!$supperted) {
            return;
        }

        $process = $this->getMockBuilder('Composer\Util\ProcessExecutor')->getMock();
        $process->expects(static::any())
            ->method('execute')
            ->willReturnCallback(function () {
                return 0;
            })
        ;

        $repoConfig = array(
            'url' => $url,
            'asset-type' => 'bower',
            'filename' => 'bower.json',
        );

        /** @var IOInterface $io */
        /** @var ProcessExecutor $process */
        $driver = new SvnDriver($repoConfig, $io, $this->config, $process, null);
        $driver->initialize();

        static::assertEquals($urlUsed, $driver->getUrl());
    }
}
