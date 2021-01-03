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

use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Repository\Vcs\VcsDriverInterface;

/**
 * Mock vcs driver.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class MockVcsDriver implements VcsDriverInterface
{
    /**
     * @var bool
     */
    public static $supported = true;

    /**
     * @var mixed
     */
    public $contents;

    public function initialize()
    {
        // no action
    }

    public function getComposerInformation($identifier)
    {
    }

    public function getRootIdentifier()
    {
    }

    public function getBranches()
    {
        return array();
    }

    public function getTags()
    {
        return array();
    }

    public function getDist($identifier)
    {
    }

    public function getSource($identifier)
    {
    }

    public function getUrl()
    {
    }

    public function hasComposerFile($identifier)
    {
        return false;
    }

    public function cleanup()
    {
        // no action
    }

    public static function supports(IOInterface $io, Config $config, $url, $deep = false)
    {
        return static::$supported;
    }

    public function getFileContent($file, $identifier)
    {
    }

    public function getChangeDate($identifier)
    {
        return new \DateTime();
    }

    /**
     * @return mixed
     */
    protected function getContents()
    {
        return $this->contents;
    }
}
