<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Installer;

use Composer\Util\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Glob;

/**
 * Manager of ignore patterns for bower.
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
class BowerIgnoreManager
{
    /**
     * @var Finder
     */
    private $files;

    /**
     * @var Finder
     */
    private $dirs;

    /**
     * Adds an ignore pattern.
     *
     * @param string $pattern The pattern
     */
    public function addPattern($pattern)
    {
        if ('/' === substr($pattern, -1)) {
            $this->addDirPattern(substr($pattern, 0, -1));
        } else {
            $this->addFilePattern($pattern);
        }
    }

    /**
     * Deletes all files and directories that matches patterns in specified directory.
     *
     * @param string          $dir        The path to the directory
     * @param Filesystem|null $filesystem
     */
    public function deleteInDir($dir, Filesystem $filesystem = null)
    {
        $filesystem = $filesystem ?: new Filesystem();

        $files = null === $this->files ? array() : iterator_to_array($this->files->in($dir));
        $dirs = null === $this->dirs ? array() : iterator_to_array($this->dirs->in($dir));

        foreach ($dirs as $path) {
            $filesystem->removeDirectory($path->getRealpath());
        }

        foreach ($files as $path) {
            $filesystem->remove($path->getRealpath());
        }
    }

    /**
     * Adds a pattern
     *
     * @param string $pattern The pattern
     */
    private function addFilePattern($pattern)
    {
        if (null === $this->files) {
            $this->files = Finder::create()->ignoreDotFiles(false)->ignoreVCS(false);
        }

        $this->addPatternToFinder($this->files, $pattern);
    }

    /**
     * Adds a pattern for only directory
     *
     * @param string $pattern The pattern
     */
    private function addDirPattern($pattern)
    {
        if (null === $this->dirs) {
            $this->dirs = Finder::create()->ignoreDotFiles(false)->ignoreVCS(false)->directories();
        }

        $this->addPatternToFinder($this->dirs, $pattern);
    }

    /**
     * Registers a pattern to finder
     *
     * @param Finder $finder
     * @param string $pattern The pattern
     */
    private function addPatternToFinder(Finder $finder, $pattern)
    {
        $path = true;
        if (0 === strpos($pattern, '!')) {
            $pattern = substr($pattern, 1);
            $path = false;
        }

        $start = false;
        if (0 === strpos($pattern, '/')) {
            $pattern = substr($pattern, 1);
            $start = true;
        }

        $pattern = substr(Glob::toRegex($pattern, false), 2, -2);
        $pattern = strtr($pattern, array('[^/]*[^/]*/' => '(|.*/)(?<=^|/)', '[^/]*[^/]*' => '.*'));
        $pattern = '#'.($start ? '^' : '').$pattern.'#';

        if ($path) {
            $finder->path($pattern);
        } else {
            $finder->notPath($pattern);
        }
    }
}
