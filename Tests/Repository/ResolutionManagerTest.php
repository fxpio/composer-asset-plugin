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

use Fxp\Composer\AssetPlugin\Repository\ResolutionManager;

/**
 * Tests of Resolution Manager.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class ResolutionManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testSolveResolutions()
    {
        $rm = new ResolutionManager(array(
            'foo/bar' => '^2.2.0',
            'bar/foo' => '^0.2.0',
        ));

        $data = $rm->solveResolutions(array(
            'require' => array(
                'foo/bar' => '2.0.*',
                'foo/baz' => '~1.0',
            ),
            'require-dev' => array(
                'bar/foo' => '^0.1.0',
                'test/dev' => '~1.0@dev',
            ),
        ));

        $expected = array(
            'require' => array(
                'foo/bar' => '^2.2.0',
                'foo/baz' => '~1.0',
            ),
            'require-dev' => array(
                'bar/foo' => '^0.2.0',
                'test/dev' => '~1.0@dev',
            ),
        );

        $this->assertSame($expected, $data);
    }
}
