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
 * Manager of ignore patterns.
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class IgnoreManager
{
    /**
     * @var string
     */
    protected $installDir;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @var bool
     */
    protected $hasPattern;

    /**
     * @var Finder
     */
    private $finder;

    /**
     * Constructor.
     *
     * @param string          $installDir The install dir
     * @param Filesystem|null $filesystem The filesystem
     */
    public function __construct($installDir, Filesystem $filesystem = null)
    {
        $this->installDir = $installDir;
        $this->filesystem = $filesystem ?: new Filesystem();
        $this->enabled = true;
        $this->hasPattern = false;
        $this->finder = Finder::create()->ignoreVCS(true)->ignoreDotFiles(false);
        $this->finder->path('/^\//');
    }

    /**
     * Enable or not this ignore files manager.
     *
     * @param bool $enabled
     *
     * @return self
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool) $enabled;

        return $this;
    }

    /**
     * Check if this ignore files manager is enabled.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Check if a pattern is added.
     *
     * @return bool
     */
    public function hasPattern()
    {
        return $this->hasPattern;
    }

    /**
     * Adds an ignore pattern.
     *
     * @param string $pattern The pattern
     */
    public function addPattern($pattern)
    {
        $this->doAddPattern($this->convertPattern($pattern));
        $this->hasPattern = true;
    }

    /**
     * Deletes all files and directories that matches patterns.
     */
    public function cleanup()
    {
        if ($this->isEnabled() && $this->hasPattern() && realpath($this->installDir)) {
            $paths = iterator_to_array($this->finder->in($this->installDir));

            /* @var \SplFileInfo $path */
            foreach ($paths as $path) {
                $this->filesystem->remove($path);
            }
        }
    }

    /**
     * Action for Add an ignore pattern.
     *
     * @param string $pattern The pattern
     */
    public function doAddPattern($pattern)
    {
        if (0 === strpos($pattern, '!')) {
            $this->finder->notPath(Glob::toRegex(substr($pattern, 1), true, false));
        } else {
            $this->finder->path(Glob::toRegex($pattern, true, false));
        }
    }

    /**
     * Converter pattern to glob.
     *
     * @param string $pattern The pattern
     *
     * @return string The pattern converted
     */
    protected function convertPattern($pattern)
    {
        $pattern = trim($pattern, '/');
        $prefix = 0 === strpos($pattern, '!') ? '!' : '';
        $searchPattern = ltrim($pattern, '!');

        if ('*' === $searchPattern) {
            $this->doAddPattern($this->convertPattern($prefix . '.*'));
        } elseif ('**/.*' === $searchPattern) {
            $this->doAddPattern($prefix . '.*');
        } elseif ('.*' === $searchPattern) {
            $this->doAddPattern($prefix . '**/.*');
        } elseif ((strlen($pattern) - 2) === strrpos($pattern, '/*')) {
            $this->doAddPattern(substr($pattern, 0, strlen($pattern) - 2));
        }
        $this->doAddPattern($pattern . '/*');

        return $pattern;
    }
}
