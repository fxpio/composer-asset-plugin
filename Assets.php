<?php

/**
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin;

use Composer\Composer;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Assets factory.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class Assets
{
    /**
     * @var array
     */
    protected static $typeClasses = array(
        'npm'   => 'Fxp\Composer\AssetPlugin\Type\NpmAssetType',
        'bower' => 'Fxp\Composer\AssetPlugin\Type\BowerAssetType',
    );

    /**
     * Creates asset type.
     *
     * @param string $type
     *
     * @return AssetTypeInterface
     *
     * @throws \InvalidArgumentException When the asset type does not exist
     */
    public static function createType($type)
    {
        if (!isset(static::$typeClasses[$type])) {
            throw new \InvalidArgumentException('The asset type "' . $type . '" does not exist, only "' . implode('", "', array_keys(static::getTypes())) . '" are accepted');
        }

        $class = static::$typeClasses[$type];

        return new $class();
    }

    /**
     * Gets the asset types.
     *
     * @return array
     */
    public static function getTypes()
    {
        return array_keys(static::$typeClasses);
    }
}
