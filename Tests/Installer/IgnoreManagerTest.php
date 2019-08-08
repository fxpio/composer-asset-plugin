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
 *
 * @internal
 */
final class IgnoreManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    private $target;

    protected function setUp()
    {
        $fs = new Filesystem();
        $this->target = sys_get_temp_dir().'/composer-foo';

        foreach ($this->getFixtureFiles() as $filename) {
            $path = $this->target.'/'.$filename;
            $fs->ensureDirectoryExists(\dirname($path));
            @file_put_contents($path, '');
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

        static::assertFileNotExists($this->target.'/.hidden');
        static::assertFileExists($this->target.'/CHANGELOG');
        static::assertFileNotExists($this->target.'/README');

        static::assertFileNotExists($this->target.'/lib/autoload.php');
        static::assertFileNotExists($this->target.'/lib');

        static::assertFileNotExists($this->target.'/src/.hidden');
        static::assertFileNotExists($this->target.'/src/doc');
        static::assertFileExists($this->target.'/src');

        static::assertFileNotExists($this->target.'/src/foo/.hidden');
        static::assertFileExists($this->target.'/src/foo/empty.html');
        static::assertFileNotExists($this->target.'/src/foo/empty.md');
        static::assertFileNotExists($this->target.'/src/foo/empty.txt');
        static::assertFileExists($this->target.'/src/foo/small.txt');
        static::assertFileExists($this->target.'/src/foo');

        static::assertFileExists($this->target.'/src/lib/empty.txt');
        static::assertFileExists($this->target.'/src/lib');

        static::assertFileExists($this->target.'/src/lib/foo/empty.txt');
        static::assertFileExists($this->target.'/src/lib/foo/small.txt');
        static::assertFileExists($this->target.'/src/lib/foo');

        static::assertFileExists($this->target.'/src/tests/empty.html');
        static::assertFileExists($this->target.'/src/tests');

        static::assertFileNotExists($this->target.'/tests/bootstrap.php');
        static::assertFileNotExists($this->target.'/tests');
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

        static::assertFileExists($this->target.'/.hidden');
        static::assertFileExists($this->target.'/CHANGELOG');
        static::assertFileExists($this->target.'/README');

        static::assertFileExists($this->target.'/lib/autoload.php');
        static::assertFileExists($this->target.'/lib');

        static::assertFileExists($this->target.'/src/.hidden');
        static::assertFileExists($this->target.'/src/doc');
        static::assertFileExists($this->target.'/src');

        static::assertFileExists($this->target.'/src/foo/.hidden');
        static::assertFileExists($this->target.'/src/foo/empty.html');
        static::assertFileExists($this->target.'/src/foo/empty.md');
        static::assertFileExists($this->target.'/src/foo/empty.txt');
        static::assertFileExists($this->target.'/src/foo/small.txt');
        static::assertFileExists($this->target.'/src/foo');

        static::assertFileExists($this->target.'/src/lib/empty.txt');
        static::assertFileExists($this->target.'/src/lib');

        static::assertFileExists($this->target.'/src/lib/foo/empty.txt');
        static::assertFileExists($this->target.'/src/lib/foo/small.txt');
        static::assertFileExists($this->target.'/src/lib/foo');

        static::assertFileExists($this->target.'/src/tests/empty.html');
        static::assertFileExists($this->target.'/src/tests');

        static::assertFileExists($this->target.'/tests/bootstrap.php');
        static::assertFileExists($this->target.'/tests');
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

        static::assertFileNotExists($this->target.'/.hidden');
        static::assertFileNotExists($this->target.'/CHANGELOG');
        static::assertFileExists($this->target.'/README');

        static::assertFileExists($this->target.'/lib/autoload.php');
        static::assertFileExists($this->target.'/lib');

        static::assertFileNotExists($this->target.'/src/.hidden');
        static::assertFileNotExists($this->target.'/src/doc');
        static::assertFileNotExists($this->target.'/src');

        static::assertFileNotExists($this->target.'/src/foo/.hidden');
        static::assertFileNotExists($this->target.'/src/foo/empty.html');
        static::assertFileNotExists($this->target.'/src/foo/empty.md');
        static::assertFileNotExists($this->target.'/src/foo/empty.txt');
        static::assertFileNotExists($this->target.'/src/foo/small.txt');
        static::assertFileNotExists($this->target.'/src/foo');

        static::assertFileNotExists($this->target.'/src/lib/empty.txt');
        static::assertFileNotExists($this->target.'/src/lib');

        static::assertFileNotExists($this->target.'/src/lib/foo/empty.txt');
        static::assertFileNotExists($this->target.'/src/lib/foo/small.txt');
        static::assertFileNotExists($this->target.'/src/lib/foo');

        static::assertFileNotExists($this->target.'/src/tests/empty.html');
        static::assertFileNotExists($this->target.'/src/tests');

        static::assertFileExists($this->target.'/tests/bootstrap.php');
        static::assertFileExists($this->target.'/tests');
    }

    public function testIgnoreAllFilesExceptAFewWithDoubleAsterisks()
    {
        $ignorer = new IgnoreManager($this->target);

        $ignorer->addPattern('**');
        $ignorer->addPattern('!/src/foo/*.txt');

        $ignorer->cleanup();

        static::assertFileExists($this->target.'/.hidden');
        static::assertFileNotExists($this->target.'/CHANGELOG');
        static::assertFileNotExists($this->target.'/README');

        static::assertFileNotExists($this->target.'/lib/autoload.php');
        static::assertFileNotExists($this->target.'/lib');

        static::assertFileNotExists($this->target.'/src/.hidden');
        static::assertFileNotExists($this->target.'/src/doc');
        static::assertFileExists($this->target.'/src');

        static::assertFileNotExists($this->target.'/src/foo/.hidden');
        static::assertFileNotExists($this->target.'/src/foo/empty.html');
        static::assertFileNotExists($this->target.'/src/foo/empty.md');
        static::assertFileExists($this->target.'/src/foo/empty.txt');
        static::assertFileExists($this->target.'/src/foo/small.txt');
        static::assertFileExists($this->target.'/src/foo');

        static::assertFileNotExists($this->target.'/src/lib/empty.txt');
        static::assertFileNotExists($this->target.'/src/lib');

        static::assertFileNotExists($this->target.'/src/lib/foo/empty.txt');
        static::assertFileNotExists($this->target.'/src/lib/foo/small.txt');
        static::assertFileNotExists($this->target.'/src/lib/foo');

        static::assertFileNotExists($this->target.'/src/tests/empty.html');
        static::assertFileNotExists($this->target.'/src/tests');

        static::assertFileNotExists($this->target.'/tests/bootstrap.php');
        static::assertFileNotExists($this->target.'/tests');
    }

    /**
     * @return array
     */
    protected function getFixtureFiles()
    {
        return array(
            '.hidden',
            'CHANGELOG',
            'README',
            'lib/autoload.php',
            'src/.hidden',
            'src/doc',
            'src/foo/.hidden',
            'src/foo/empty.html',
            'src/foo/empty.md',
            'src/foo/empty.txt',
            'src/foo/small.txt',
            'src/lib/empty.txt',
            'src/lib/foo/empty.txt',
            'src/lib/foo/small.txt',
            'src/tests/empty.html',
            'tests/bootstrap.php',
        );
    }
}
