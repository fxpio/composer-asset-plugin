<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Package;

use Composer\Package\CompletePackage;
use Fxp\Composer\AssetPlugin\Package\LazyCompletePackage;
use Fxp\Composer\AssetPlugin\Package\LazyPackageInterface;
use Fxp\Composer\AssetPlugin\Package\Loader\LazyLoaderInterface;

/**
 * Tests of lazy asset package loader.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class LazyCompletePackageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LazyPackageInterface
     */
    protected $package;

    protected function setUp()
    {
        $this->package = new LazyCompletePackage('foo', '1.0.0.0', '1.0');
    }

    protected function tearDown()
    {
        $this->package = null;
    }

    public function getConfigLazyLoader()
    {
        return array(
            array(null),
            array('lazy'),
            array('lazy-exception'),
        );
    }

    /**
     * @param string $lazyType
     *
     * @dataProvider getConfigLazyLoader
     */
    public function testMissingAssetType($lazyType)
    {
        if (null !== $lazyType) {
            $lp = 'lazy' === $lazyType
                ? new CompletePackage(
                    $this->package->getName(),
                    $this->package->getVersion(),
                    $this->package->getPrettyVersion()
                )
                : false;

            $loader = $this->getMockBuilder('Fxp\Composer\AssetPlugin\Package\Loader\LazyLoaderInterface')->getMock();
            $loader
                ->expects(static::any())
                ->method('load')
                ->willReturn($lp)
            ;

            /* @var LazyLoaderInterface$loader */
            $this->package->setLoader($loader);
        }

        static::assertSame('library', $this->package->getType());
        static::assertSame(array(), $this->package->getTransportOptions());
        static::assertNull($this->package->getTargetDir());
        static::assertSame(array(), $this->package->getExtra());
        static::assertSame(array(), $this->package->getBinaries());
        static::assertNull($this->package->getInstallationSource());
        static::assertNull($this->package->getSourceType());
        static::assertNull($this->package->getSourceUrl());
        static::assertNull($this->package->getSourceReference());
        static::assertNull($this->package->getSourceMirrors());
        static::assertSame(array(), $this->package->getSourceUrls());
        static::assertNull($this->package->getDistType());
        static::assertNull($this->package->getDistUrl());
        static::assertNull($this->package->getDistReference());
        static::assertNull($this->package->getDistSha1Checksum());
        static::assertNull($this->package->getDistMirrors());
        static::assertSame(array(), $this->package->getDistUrls());
        static::assertNull($this->package->getReleaseDate());
        static::assertSame(array(), $this->package->getRequires());
        static::assertSame(array(), $this->package->getConflicts());
        static::assertSame(array(), $this->package->getProvides());
        static::assertSame(array(), $this->package->getReplaces());
        static::assertSame(array(), $this->package->getDevRequires());
        static::assertSame(array(), $this->package->getSuggests());
        static::assertSame(array(), $this->package->getAutoload());
        static::assertSame(array(), $this->package->getDevAutoload());
        static::assertSame(array(), $this->package->getIncludePaths());
        static::assertNull($this->package->getNotificationUrl());
        static::assertSame(array(), $this->package->getArchiveExcludes());
        static::assertSame(array(), $this->package->getScripts());
        static::assertNull($this->package->getRepositories());
        static::assertSame(array(), $this->package->getLicense());
        static::assertNull($this->package->getKeywords());
        static::assertNull($this->package->getAuthors());
        static::assertNull($this->package->getDescription());
        static::assertNull($this->package->getHomepage());
        static::assertSame(array(), $this->package->getSupport());
    }
}
