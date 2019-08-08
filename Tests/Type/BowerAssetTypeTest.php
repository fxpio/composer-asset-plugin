<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Type;

use Fxp\Composer\AssetPlugin\Type\BowerAssetType;

/**
 * Tests of bower asset type.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class BowerAssetTypeTest extends AbstractAssetTypeTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->type = new BowerAssetType($this->packageConverter, $this->versionConverter);
    }

    public function testInformations()
    {
        static::assertSame('bower', $this->type->getName());
        static::assertSame('bower-asset', $this->type->getComposerVendorName());
        static::assertSame('bower-asset-library', $this->type->getComposerType());
        static::assertSame('bower.json', $this->type->getFilename());
        static::assertSame('bower-asset/foobar', $this->type->formatComposerName('foobar'));
        static::assertSame('bower-asset/foobar', $this->type->formatComposerName('bower-asset/foobar'));
    }
}
