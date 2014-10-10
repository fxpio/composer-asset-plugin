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
use Fxp\Composer\AssetPlugin\Installer\IgnoreManager;

/**
 * Tests of manager of ignore patterns.
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
class IgnoreManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $target;

    protected function setUp()
    {
        $fs = new Filesystem();
        $source = __DIR__.'/../Fixtures/foo';
        $this->target = $target = sys_get_temp_dir() . '/composer-foo';

        $it = new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS);
        $ri = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::SELF_FIRST);
        $fs->ensureDirectoryExists($target);

        /* @var \SplFileInfo $file */
        foreach ($ri as $file) {
            /* @var \RecursiveDirectoryIterator $ri */
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
        $ignorer = new IgnoreManager($this->target);
        $ignorer->addPattern('.*');
        $ignorer->addPattern('**/.*');
        $ignorer->addPattern('README');
        $ignorer->addPattern('**/*.md');
        $ignorer->addPattern('lib');
        $ignorer->addPattern('tests');
        $ignorer->addPattern('**/doc');
        $ignorer->addPattern('src/foo/*.txt');
        $ignorer->addPattern('!src/foo/small.txt');

        $ignorer->cleanup();

        $this->assertFileNotExists($this->target.'/.hidden');
        $this->assertFileExists($this->target.'/CHANGELOG');
        $this->assertFileNotExists($this->target.'/README');

        $this->assertFileNotExists($this->target.'/lib/autoload.php');
        $this->assertFileNotExists($this->target.'/lib');

        $this->assertFileNotExists($this->target.'/src/.hidden');
        $this->assertFileNotExists($this->target.'/src/doc');
        $this->assertFileExists($this->target.'/src');

        $this->assertFileNotExists($this->target.'/src/foo/.hidden');
        $this->assertFileExists($this->target.'/src/foo/empty.html');
        $this->assertFileNotExists($this->target.'/src/foo/empty.md');
        $this->assertFileNotExists($this->target.'/src/foo/empty.txt');
        $this->assertFileExists($this->target.'/src/foo/small.txt');
        $this->assertFileExists($this->target.'/src/foo');

        $this->assertFileExists($this->target.'/src/lib/empty.txt');
        $this->assertFileExists($this->target.'/src/lib');

        $this->assertFileExists($this->target.'/src/lib/foo/empty.txt');
        $this->assertFileExists($this->target.'/src/lib/foo/small.txt');
        $this->assertFileExists($this->target.'/src/lib/foo');

        $this->assertFileExists($this->target.'/src/tests/empty.html');
        $this->assertFileExists($this->target.'/src/tests');

        $this->assertFileNotExists($this->target.'/tests/bootstrap.php');
        $this->assertFileNotExists($this->target.'/tests');
    }

    public function testDeleteIgnoredFilesWithDisabledManager()
    {
        $ignorer = new IgnoreManager($this->target);
        $ignorer->setEnabled(false);
        $ignorer->addPattern('.*');
        $ignorer->addPattern('**/.*');
        $ignorer->addPattern('README');
        $ignorer->addPattern('**/*.md');
        $ignorer->addPattern('lib');
        $ignorer->addPattern('tests');
        $ignorer->addPattern('**/doc');
        $ignorer->addPattern('src/foo/*.txt');
        $ignorer->addPattern('!src/foo/small.txt');

        $ignorer->cleanup();

        $this->assertFileExists($this->target.'/.hidden');
        $this->assertFileExists($this->target.'/CHANGELOG');
        $this->assertFileExists($this->target.'/README');

        $this->assertFileExists($this->target.'/lib/autoload.php');
        $this->assertFileExists($this->target.'/lib');

        $this->assertFileExists($this->target.'/src/.hidden');
        $this->assertFileExists($this->target.'/src/doc');
        $this->assertFileExists($this->target.'/src');

        $this->assertFileExists($this->target.'/src/foo/.hidden');
        $this->assertFileExists($this->target.'/src/foo/empty.html');
        $this->assertFileExists($this->target.'/src/foo/empty.md');
        $this->assertFileExists($this->target.'/src/foo/empty.txt');
        $this->assertFileExists($this->target.'/src/foo/small.txt');
        $this->assertFileExists($this->target.'/src/foo');

        $this->assertFileExists($this->target.'/src/lib/empty.txt');
        $this->assertFileExists($this->target.'/src/lib');

        $this->assertFileExists($this->target.'/src/lib/foo/empty.txt');
        $this->assertFileExists($this->target.'/src/lib/foo/small.txt');
        $this->assertFileExists($this->target.'/src/lib/foo');

        $this->assertFileExists($this->target.'/src/tests/empty.html');
        $this->assertFileExists($this->target.'/src/tests');

        $this->assertFileExists($this->target.'/tests/bootstrap.php');
        $this->assertFileExists($this->target.'/tests');
    }

    public function testIgnoreAllFilesExceptAFew()
    {
        $ignorer = new IgnoreManager($this->target);
        $ignorer->addPattern('*');
        $ignorer->addPattern('**/.*');
        $ignorer->addPattern('!README');
        $ignorer->addPattern('!lib/*');
        $ignorer->addPattern('!tests');

        $ignorer->cleanup();

        $this->assertFileNotExists($this->target.'/.hidden');
        $this->assertFileNotExists($this->target.'/CHANGELOG');
        $this->assertFileExists($this->target.'/README');

        $this->assertFileExists($this->target.'/lib/autoload.php');
        $this->assertFileExists($this->target.'/lib');

        $this->assertFileNotExists($this->target.'/src/.hidden');
        $this->assertFileNotExists($this->target.'/src/doc');
        $this->assertFileNotExists($this->target.'/src');

        $this->assertFileNotExists($this->target.'/src/foo/.hidden');
        $this->assertFileNotExists($this->target.'/src/foo/empty.html');
        $this->assertFileNotExists($this->target.'/src/foo/empty.md');
        $this->assertFileNotExists($this->target.'/src/foo/empty.txt');
        $this->assertFileNotExists($this->target.'/src/foo/small.txt');
        $this->assertFileNotExists($this->target.'/src/foo');

        $this->assertFileNotExists($this->target.'/src/lib/empty.txt');
        $this->assertFileNotExists($this->target.'/src/lib');

        $this->assertFileNotExists($this->target.'/src/lib/foo/empty.txt');
        $this->assertFileNotExists($this->target.'/src/lib/foo/small.txt');
        $this->assertFileNotExists($this->target.'/src/lib/foo');

        $this->assertFileNotExists($this->target.'/src/tests/empty.html');
        $this->assertFileNotExists($this->target.'/src/tests');

        $this->assertFileExists($this->target.'/tests/bootstrap.php');
        $this->assertFileExists($this->target.'/tests');
    }
}
