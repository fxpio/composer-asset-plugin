<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Type;

use Fxp\Composer\AssetPlugin\Converter\SemverConverter;
use Fxp\Composer\AssetPlugin\Converter\VersionConverterInterface;

/**
 * Abstract asset type.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class AbstractAssetType implements AssetTypeInterface
{
    /**
     * @var VersionConverterInterface
     */
    protected $versionConverter;

    public function __construct()
    {
        $this->versionConverter = new SemverConverter();
    }

    /**
     * {@inheritdoc}
     */
    public function getComposerVendorName()
    {
        return $this->getName() . '-asset';
    }

    /**
     * {@inheritdoc}
     */
    public function getFilename()
    {
        return $this->getName() . '.json';
    }

    /**
     * {@inheritdoc}
     */
    public function getVersionConverter()
    {
        return $this->versionConverter;
    }
}
