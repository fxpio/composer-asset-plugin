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
namespace Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\Cache;

use Fxp\Composer\AssetPlugin\Repository\AbstractAssetsRepository;
use Fxp\Composer\AssetPlugin\Repository\Cache\AbstractAssetsRepositoryCache;

/**
 * Class MockAssetRepositoryCache.
 */
class MockAssetRepositoryCache extends AbstractAssetsRepositoryCache
{
    /**
     * @var array
     */
    public $options = array();

    /**
     * MockAssetRepositoryCache constructor.
     *
     * @param array $options
     */
    public function __construct($options = array())
    {
        $this->options = $options;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function findItems($packageName, $assetsRepositoryType)
    {
        if (preg_match('/^'.$assetsRepositoryType.'-asset\/existing/uis', $packageName)) {
            return array(
                array(
                    'version' => '1.1.1.0',
                    'dist' => array(
                        'url' => '/path/to/not-existing/archive-1.1.1.0.zip',
                        'type' => 'zip',
                    ),
                ),
            );
        }

        return array();
    }
}
