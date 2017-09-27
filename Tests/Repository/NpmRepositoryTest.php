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
use Composer\Downloader\TransportException;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Fxp\Composer\AssetPlugin\Repository\NpmRepository;

/**
 * Tests of NPM repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class NpmRepositoryTest extends AbstractAssetsRepositoryTest
{
    /**
     * {@inheritdoc}
     */
    protected function getType()
    {
        return 'npm';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRegistry(array $repoConfig, IOInterface $io, Config $config, EventDispatcher $eventDispatcher = null)
    {
        return new NpmRepository($repoConfig, $io, $config, $eventDispatcher);
    }

    /**
     * {@inheritdoc}
     */
    protected function getMockPackageForVcsConfig()
    {
        return array(
            'repository' => array(
                'type' => 'vcs',
                'url' => 'http://foo.tld',
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getMockSearchResult($name = 'mock-package')
    {
        return array();
    }

    public function testWhatProvidesWithCamelcasePackageName()
    {
        $name = $this->getType().'-asset/CamelCasePackage';
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects($this->any())
            ->method('getContents')
            ->will($this->throwException(new TransportException('Package not found', 404)));

        $this->assertCount(0, $this->rm->getRepositories());
        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
        $this->assertCount(0, $this->rm->getRepositories());
    }

    public function testWatProvidesWithoutRepositoryUrl()
    {
        $name = $this->getType().'-asset/foobar';
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects($this->any())
            ->method('getContents')
            ->will($this->returnValue(json_encode(array(
                'repository' => array(
                    'type' => 'vcs',
                ),
                'versions' => array(
                    '1.0.0' => array(
                        'name' => 'foobar',
                        'version' => '0.0.1',
                        'dependencies' => array(),
                        'dist' => array(
                            'shasum' => '1d408b3fdb76923b9543d96fb4c9dfd535d9cb5d',
                            'tarball' => 'http://registry.tld/foobar/-/foobar-1.0.0.tgz',
                        ),
                    ),
                ),
                'time' => array(
                    '1.0.0' => '2016-09-20T13:48:47.730Z',
                ),
            ))));

        $this->assertCount(0, $this->rm->getRepositories());
        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
        $this->assertCount(1, $this->rm->getRepositories());
    }

    public function testWhatProvidesWithBrokenVersionConstraint()
    {
        $name = $this->getType().'-asset/foobar';
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects($this->any())
            ->method('getContents')
            ->will($this->returnValue(json_encode(array(
                'repository' => array(
                    'type' => 'vcs',
                ),
                'versions' => array(
                    '1.0.0' => array(
                        'name' => 'foobar',
                        'version' => '0.0.1',
                        'dependencies' => array(),
                        'dist' => array(
                            'shasum' => '1d408b3fdb76923b9543d96fb4c9dfd535d9cb5d',
                            'tarball' => 'http://registry.tld/foobar/-/foobar-1.0.0.tgz',
                        ),
                    ),
                    '1.0.1' => array(
                        'name' => 'foobar',
                        'version' => '0.0.1',
                        'dependencies' => array(
                            // This constraint is invalid. Whole version package version should be skipped.
                            'library1' => '^1.2,,<2.0',
                        ),
                        'dist' => array(
                            'shasum' => '1d408b3fdb76923b9543d96fb4c9acd535d9cb7a',
                            'tarball' => 'http://registry.tld/foobar/-/foobar-1.0.1.tgz',
                        ),
                    ),
                    '1.0.2' => array(
                        'name' => 'foobar',
                        'version' => '0.0.1',
                        'dependencies' => array(
                            'library1' => '^1.2,<2.0',
                        ),
                        'dist' => array(
                            'shasum' => '1d408b3fdb76923b9543d96fb4c9acd535d9cb7a',
                            'tarball' => 'http://registry.tld/foobar/-/foobar-1.0.1.tgz',
                        ),
                    ),
                ),
                'time' => array(
                    '1.0.0' => '2016-09-20T13:48:47.730Z',
                ),
            ))));

        $this->assertCount(0, $this->rm->getRepositories());
        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
        $this->assertCount(1, $this->rm->getRepositories());
        $this->assertCount(2, $this->rm->getRepositories()[0]->getPackages());
    }

    /**
     * @expectedException \Fxp\Composer\AssetPlugin\Exception\InvalidCreateRepositoryException
     * @expectedExceptionMessage "repository.url" parameter of "foobar"
     */
    public function testWatProvidesWithoutRepositoryUrlAndWithoutVersions()
    {
        $name = $this->getType().'-asset/foobar';
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects($this->any())
            ->method('getContents')
            ->will($this->returnValue(json_encode(array())));

        $this->assertCount(0, $this->rm->getRepositories());

        $this->registry->whatProvides($this->pool, $name);
    }

    public function testWhatProvidesWithGitPlusHttpsUrl()
    {
        $name = $this->getType().'-asset/existing';
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects($this->any())
            ->method('getContents')
            ->will($this->returnValue(json_encode(array(
                'repository' => array(
                    'type' => 'vcs',
                    'url' => 'git+https://foo.tld',
                ),
            ))));

        $this->assertCount(0, $this->rm->getRepositories());
        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
        $this->assertCount(0, $this->registry->whatProvides($this->pool, $name));
        $this->assertCount(1, $this->rm->getRepositories());
    }
}
