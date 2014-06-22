<?php

/**
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Type;

/**
 * Bower asset type.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class BowerAssetType extends AbstractAssetType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'bower';
    }

    /**
     * {@inheritdoc}
     */
    public function convert(array $data)
    {
        $package = array(
            'name'    => $this->getComposerVendorName() . '/' . $data['name'],
            'version' => $data['version'],
        );

        return $package;
    }
}
