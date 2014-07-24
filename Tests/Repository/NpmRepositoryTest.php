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
                'url'  => 'http://foo.tld',
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
}
