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
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Fxp\Composer\AssetPlugin\Repository\BowerPrivateRepository;

/**
 * Tests of Private Bower repository.
 *
 * @author Marcus Stüben <marcus@it-stueben.de>
 *
 * @internal
 */
final class BowerPrivateRepositoryTest extends AbstractAssetsRepositoryTest
{
    /**
     * @expectedException \Fxp\Composer\AssetPlugin\Exception\InvalidCreateRepositoryException
     * @expectedExceptionMessage The "repository.url" parameter of "existing" bower asset package must be present for create a VCS Repository
     */
    public function testWhatProvidesWithInvalidPrivateUrl()
    {
        $name = $this->getType().'-asset/existing';
        $rfs = $this->replaceRegistryRfsByMock();
        $rfs->expects(static::any())
            ->method('getContents')
            ->willReturn(json_encode(array()))
        ;

        $this->registry->whatProvides($this->pool, $name);
    }

    protected function getType()
    {
        return 'bower';
    }

    protected function getRegistry(array $repoConfig, IOInterface $io, Config $config, EventDispatcher $eventDispatcher = null)
    {
        return new BowerPrivateRepository($repoConfig, $io, $config, $eventDispatcher);
    }

    protected function getMockPackageForVcsConfig()
    {
        return array(
            'url' => 'http://foo.tld',
        );
    }

    protected function getMockSearchResult($name = 'mock-package')
    {
        return array(
            array(
                'name' => $name,
            ),
        );
    }

    protected function getCustomRepoConfig()
    {
        return array(
            'private-registry-url' => 'http://foo.tld',
        );
    }
}
