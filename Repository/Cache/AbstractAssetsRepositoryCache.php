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
namespace Fxp\Composer\AssetPlugin\Repository\Cache;

/**
 * Class AbstractAssetsRepositoryCache.
 */
abstract class AbstractAssetsRepositoryCache
{
    /**
     * @var self[] list of registered cache objects
     */
    private static $_cacheList = array();

    /**
     * @return self[] returns a list of registered cache objects
     */
    public static function getCacheList()
    {
        return self::$_cacheList;
    }

    /**
     * @return bool
     */
    final public static function cleanRegistration()
    {
        $className = get_called_class();
        if ($className == __CLASS__) {
            self::$_cacheList = array();

            return true;
        } elseif (isset(self::$_cacheList[$className])) {
            unset(self::$_cacheList[$className]);

            return true;
        }

        return false;
    }

    public function __construct()
    {
        $this->register();
    }

    /**
     * @param string $packageName          package name
     * @param string $assetsRepositoryType bower or npm
     *
     * @return null|array list of items. Each item must be an associative array, which contains next elements:
     *                    - version => '1.1.1.0'
     *                    - dist => array('type' => 'zip', 'url' => 'path/to/archive.zip')
     */
    abstract public function findItems($packageName, $assetsRepositoryType);

    /**
     * register cache object in cache list.
     */
    protected function register()
    {
        $className = get_class($this);
        self::$_cacheList[$className] = $this;
    }

    /**
     * @return bool unregister cache object in cache list
     */
    public function unRegister()
    {
        $className = get_class($this);
        if (isset(self::$_cacheList[$className])) {
            unset(self::$_cacheList[$className]);

            return true;
        }

        return false;
    }
}
