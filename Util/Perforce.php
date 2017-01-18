<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Util;

use Composer\IO\IOInterface;
use Composer\Util\Perforce as BasePerforce;
use Composer\Util\ProcessExecutor;

/**
 * Helper for perforce driver.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class Perforce extends BasePerforce
{
    /**
     * @var string
     */
    protected $filename;

    /**
     * @param array $repoConfig
     */
    public function initialize($repoConfig)
    {
        parent::initialize($repoConfig);

        $this->filename = (string) $repoConfig['filename'];
    }

    /**
     * @param string $identifier
     *
     * @return array|string
     */
    public function getComposerInformation($identifier)
    {
        $composerFileContent = $this->getFileContent($this->filename, $identifier);

        return !$composerFileContent
            ? null
            : json_decode($composerFileContent, true);
    }

    /**
     * Create perforce helper.
     *
     * @param array           $repoConfig
     * @param int|string      $port
     * @param string          $path
     * @param ProcessExecutor $process
     * @param IOInterface     $io
     *
     * @return Perforce
     */
    public static function create($repoConfig, $port, $path, ProcessExecutor $process, IOInterface $io)
    {
        $isWindows = defined('PHP_WINDOWS_VERSION_BUILD');
        $perforce = new self($repoConfig, $port, $path, $process, $isWindows, $io);

        return $perforce;
    }
}
