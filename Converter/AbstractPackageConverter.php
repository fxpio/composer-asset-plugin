<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Converter;

use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;
use Fxp\Composer\AssetPlugin\Util\Validator;

/**
 * Abstract class for converter for asset package to composer package.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class AbstractPackageConverter implements PackageConverterInterface
{
    /**
     * @var AssetTypeInterface
     */
    protected $assetType;

    /**
     * Constructor.
     *
     * @param AssetTypeInterface $assetType
     */
    public function __construct(AssetTypeInterface $assetType)
    {
        $this->assetType = $assetType;
    }

    /**
     * Converts the all keys (keys, dependencies and extra keys).
     *
     * @param array $asset        The asset data
     * @param array $keys         The map of asset key and composer key
     * @param array $dependencies The map of asset dependency key and composer dependency key
     * @param array $extras       The map of asset key and composer extra key
     * @param array $vcsRepos     The list of new vcs configs
     *
     * @return array The composer package converted
     */
    protected function convertData(array $asset, array $keys, array $dependencies, array $extras, array &$vcsRepos = array())
    {
        $composer = array();

        foreach ($keys as $assetKey => $composerKey) {
            $this->convertKey($asset, $assetKey, $composer, $composerKey);
        }

        foreach ($dependencies as $assetKey => $composerKey) {
            $this->convertDependencies($asset, $assetKey, $composer, $composerKey, $vcsRepos);
        }

        foreach ($extras as $assetKey => $composerKey) {
            $this->convertExtraKey($asset, $assetKey, $composer, $composerKey);
        }

        return $composer;
    }

    /**
     * Converts the simple key of package.
     *
     * @param array        $asset       The asset data
     * @param string       $assetKey    The asset key
     * @param array        $composer    The composer data
     * @param string|array $composerKey The composer key or array with composer key name and closure
     *
     * @throws \InvalidArgumentException When the 'composerKey' argument of asset packager converter is not an string or an array with the composer key and closure
     */
    protected function convertKey(array $asset, $assetKey, array &$composer, $composerKey)
    {
        if (is_array($composerKey)) {
            $this->convertArrayKey($asset, $assetKey, $composer, $composerKey);
        } else {
            $this->convertStringKey($asset, $assetKey, $composer, $composerKey);
        }
    }

    /**
     * Converts the extra key of package.
     *
     * @param array        $asset       The asset data
     * @param string       $assetKey    The asset extra key
     * @param array        $composer    The composer data
     * @param string|array $composerKey The composer extra key or array with composer extra key name and closure
     * @param string       $extraKey    The extra key name
     */
    protected function convertExtraKey(array $asset, $assetKey, array &$composer, $composerKey, $extraKey = 'extra')
    {
        $extra = isset($composer[$extraKey]) ? $composer[$extraKey] : array();

        $this->convertKey($asset, $assetKey, $extra, $composerKey);

        if (count($extra) > 0) {
            $composer[$extraKey] = $extra;
        }
    }

    /**
     * Converts simple key of package.
     *
     * @param array  $asset       The asset data
     * @param string $assetKey    The asset key of dependencies
     * @param array  $composer    The composer data
     * @param string $composerKey The composer key of dependencies
     * @param array  $vcsRepos    The list of new vcs configs
     */
    protected function convertDependencies(array $asset, $assetKey, array &$composer, $composerKey, array &$vcsRepos = array())
    {
        if (isset($asset[$assetKey]) && is_array($asset[$assetKey])) {
            $newDependencies = array();

            foreach ($asset[$assetKey] as $dependency => $version) {
                list($dependency, $version) = $this->convertDependency($dependency, $version, $vcsRepos);
                $version = $this->assetType->getVersionConverter()->convertRange($version);
                $newDependencies[$this->assetType->getComposerVendorName() . '/' . $dependency] = $version;
            }

            $composer[$composerKey] = $newDependencies;
        }
    }

    /**
     * Convert the .
     *
     * @param string $dependency The dependency
     * @param string $version    The version
     * @param array  $vcsRepos   The list of new vcs configs
     *
     * @return string[] The new dependency and the new version
     */
    protected function convertDependency($dependency, $version, array &$vcsRepos = array())
    {
        list($dependency, $version) = $this->checkUrlVersion($dependency, $version, $vcsRepos);
        list($dependency, $version) = $this->checkAliasVersion($dependency, $version);

        return array($dependency, $version);
    }

    /**
     * Checks if the version is a URL version.
     *
     * @param string $dependency The dependency
     * @param string $version    The version
     * @param array  $vcsRepos   The list of new vcs configs
     *
     * @return string[] The new dependency and the new version
     */
    protected function checkUrlVersion($dependency, $version, array &$vcsRepos = array())
    {
        if (preg_match('/(\:\/\/)|\@/', $version)) {
            $pos = strpos($version, '#');

            // number version or empty version
            if (false !== $pos) {
                $url = substr($version, 0, $pos);
                $version = substr($version, $pos + 1);
            } else {
                $url = $version;
                $version = 'default';
            }

            // sha version or branch verison
            if (preg_match('{^[0-9a-f]{40}$}', $version)) {
                $version = 'dev-default#' . $version;
            } elseif (!Validator::validateTag($version, $this->assetType)) {
                $oldVersion = $version;
                $version = 'dev-' . $version;

                if (!Validator::validateBranch($oldVersion)) {
                    $version .= ' || ' . $oldVersion;
                }
            }

            $vcsRepos[] = array(
                'type' => sprintf('%s-vcs', $this->assetType->getName()),
                'url'  => $url,
            );
        }

        return array($dependency, $version);
    }

    /**
     * Checks if the version is a alias version.
     *
     * @param string $dependency The dependency
     * @param string $version    The version
     *
     * @return string[] The new dependency and the new version
     */
    protected function checkAliasVersion($dependency, $version)
    {
        $pos = strpos($version, '#');

        if (false !== $pos && !preg_match('{[0-9a-f]{40}$}', $version)) {
            $dependency = substr($version, 0, $pos);
            $version = substr($version, $pos + 1);
            $dependency .= '-' . $version;
        }

        return array($dependency, $version);
    }

    /**
     * Converts the simple key of package.
     *
     * @param array  $asset       The asset data
     * @param string $assetKey    The asset key
     * @param array  $composer    The composer data
     * @param string $composerKey The composer key
     */
    private function convertStringKey(array $asset, $assetKey, array &$composer, $composerKey)
    {
        if (isset($asset[$assetKey])) {
            $composer[$composerKey] = $asset[$assetKey];
        }
    }

    /**
     * Converts the simple key of package.
     *
     * @param array  $asset       The asset data
     * @param string $assetKey    The asset key
     * @param array  $composer    The composer data
     * @param array  $composerKey The array with composer key name and closure
     *
     * @throws \InvalidArgumentException When the 'composerKey' argument of asset packager converter is not an string or an array with the composer key and closure
     */
    private function convertArrayKey(array $asset, $assetKey, array &$composer, $composerKey)
    {
        if (2 !== count($composerKey)
            || !is_string($composerKey[0]) || !$composerKey[1] instanceof \Closure) {
            throw new \InvalidArgumentException('The "composerKey" argument of asset packager converter must be an string or an array with the composer key and closure');
        }

        $closure = $composerKey[1];
        $composerKey = $composerKey[0];
        $data = isset($asset[$assetKey]) ? $asset[$assetKey] : null;
        $previousData = isset($composer[$composerKey]) ? $composer[$composerKey] : null;
        $data = $closure($data, $previousData);

        if (null !== $data) {
            $composer[$composerKey] = $data;
        }
    }
}
