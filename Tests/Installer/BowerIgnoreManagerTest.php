<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Installer;

use Composer\Util\Filesystem;
use Fxp\Composer\AssetPlugin\Installer\BowerIgnoreManager;

/**
 * Tests of manager of ignore patterns.
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
class BowerIgnoreManagerTest extends \PHPUnit_Framework_TestCase
{
    private $target;

    protected function setUp()
    {
        $fs = new Filesystem();
        $source = __DIR__.'/../Fixtures/foo';
        $this->target = $target = sys_get_temp_dir() . '/composer-foo';

        $it = new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS);
        $ri = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::SELF_FIRST);
        $fs->ensureDirectoryExists($target);

        foreach ($ri as $file) {
            $targetPath = $target . DIRECTORY_SEPARATOR . $ri->getSubPathName();
            if ($file->isDir()) {
                $fs->ensureDirectoryExists($targetPath);
            } else {
                copy($file->getPathname(), $targetPath);
            }
        }
    }

    protected function tearDown()
    {
        $fs = new Filesystem();
        $fs->remove($this->target);
    }

    public function testDeleteIgnoredFiles()
    {
        $ignorer = new BowerIgnoreManager();
        $ignorer->addPattern('**/.*');
        $ignorer->addPattern('README');
        $ignorer->addPattern('*.md');
        $ignorer->addPattern('/lib');
        $ignorer->addPattern('tests');
        $ignorer->addPattern('doc/');
        $ignorer->addPattern('src/**/foo/*.txt');

        $ignorer->deleteInDir($this->target);

        $this->assertFileExists($this->target.'/CHANGELOG');
        $this->assertFileNotExists($this->target.'/README');

        $this->assertFileExists($this->target.'/src/lib');
        $this->assertFileNotExists($this->target.'/lib');

        $this->assertFileNotExists($this->target.'/tests');
        $this->assertFileNotExists($this->target.'/src/tests');

        $this->assertFileNotExists($this->target.'/src/foo/empty.md');

        $this->assertFileExists($this->target.'/src/lib/empty.txt');
        $this->assertFileExists($this->target.'/src/foo/empty.html');
        $this->assertFileNotExists($this->target.'/src/foo/empty.txt');

        $this->assertFileNotExists($this->target.'/.hidden');
        $this->assertFileNotExists($this->target.'/src/.hidden');
        $this->assertFileNotExists($this->target.'/src/foo/.hidden');

        $this->assertFileExists($this->target.'/src/doc');
    }
}
