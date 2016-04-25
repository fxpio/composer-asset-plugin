<?php
/**
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 * (c) Danil Syromolotov <pelmennoteam@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Fxp\Composer\AssetPlugin\Tests\Repository\Cache;

use Fxp\Composer\AssetPlugin\Repository\Cache\AbstractAssetsRepositoryCache;
use Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\Cache\MockAssetRepositoryCache;

/**
 * Class AbstractAssetsRepositoryCacheTest.
 */
class AbstractAssetsRepositoryCacheTest extends \PHPUnit_Framework_TestCase
{
    public function testStaticListItems()
    {
        $cacheList = AbstractAssetsRepositoryCache::getCacheList();
        $this->assertCount(0, $cacheList);

        $cacheInstance = new MockAssetRepositoryCache(array(
            'test' => 'option',
        ));

        $this->assertInstanceOf(
            'Fxp\Composer\AssetPlugin\Repository\Cache\AbstractAssetsRepositoryCache',
            $cacheInstance
        );

        $this->assertObjectHasAttribute('options', $cacheInstance);
        $this->assertArrayHasKey('test', $cacheInstance->options);
        $this->assertEquals('option', $cacheInstance->options['test']);

        $cacheList = AbstractAssetsRepositoryCache::getCacheList();
        $this->assertCount(1, $cacheList);

        $this->assertEquals(true, $cacheInstance->unRegister());

        $this->assertEquals(true, AbstractAssetsRepositoryCache::cleanRegistration());

        $cacheList = AbstractAssetsRepositoryCache::getCacheList();
        $this->assertCount(0, $cacheList);

        $this->assertEquals(false, $cacheInstance->unRegister());

        $this->assertEquals(false, MockAssetRepositoryCache::cleanRegistration());
    }
}
