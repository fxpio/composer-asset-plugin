<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository\Vcs;

/**
 * Mock vcs driver for packages test.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class MockVcsDriverWithException extends MockVcsDriver
{
    /**
     * {@inheritdoc}
     *
     * @throws
     */
    public function getTags()
    {
        throw new \ErrorException('Error to retrieve the tags');
    }
}
