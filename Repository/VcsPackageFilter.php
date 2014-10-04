<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Repository;

use Composer\Package\Link;
use Composer\Package\Package;
use Composer\Package\RootPackageInterface;
use Composer\Package\Version\VersionParser;
use Composer\Package\LinkConstraint\LinkConstraintInterface;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Filters the asset packages imported into VCS repository to optimize
 * performance when getting the informations of packages.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class VcsPackageFilter
{
    /**
     * @var RootPackageInterface
     */
    protected $package;

    /**
     * @var VersionParser
     */
    protected $versionParser;

    /**
     * @var array
     */
    protected $requires;

    /**
     * Constructor.
     *
     * @param RootPackageInterface $package The root package
     */
    public function __construct(RootPackageInterface $package)
    {
        $this->package = $package;
        $this->versionParser = new VersionParser();

        $this->initialize();
    }

    /**
     * Check if the version must be skipped.
     *
     * @param AssetTypeInterface $assetType The asset type
     * @param string             $name      The composer package name
     * @param string             $version   The version
     *
     * @return bool
     */
    public function skip(AssetTypeInterface $assetType, $name, $version)
    {
        if (!isset($this->requires[$name])) {
            return false;
        }

        /* @var Link $require */
        $require = $this->requires[$name];

        try {
            $cVersion = $assetType->getVersionConverter()->convertVersion($version);
            $normalizedVersion = $this->versionParser->normalize($cVersion);

            return !$this->satisfy($require, $normalizedVersion);
        } catch (\Exception $ex) {
            return true;
        }
    }

    /**
     * Check if the require dependency has a satisfactory version and stability.
     *
     * @param Link   $require           The require link defined in root package.
     * @param string $normalizedVersion The normalized version
     *
     * @return bool
     */
    protected function satisfy(Link $require, $normalizedVersion)
    {
        return $this->satisfyVersion($require, $normalizedVersion)
            && $this->satisfyStability($require, $normalizedVersion);
    }

    /**
     * Check if the require dependency has a satisfactory version.
     *
     * @param Link   $require           The require link defined in root package.
     * @param string $normalizedVersion The normalized version
     *
     * @return bool
     */
    protected function satisfyVersion(Link $require, $normalizedVersion)
    {
        $constraintSame = $this->versionParser->parseConstraints($normalizedVersion);
        $sameVersion = (bool) $require->getConstraint()->matches($constraintSame);

        $normalizedVersion = $this->getVersionConstraint($normalizedVersion);
        $constraint = $this->getVersionConstraint($normalizedVersion);

        return (bool) $require->getConstraint()->matches($constraint) || $sameVersion;
    }

    /**
     * Check if the require dependency has a satisfactory stability.
     *
     * @param Link   $require           The require link defined in root package.
     * @param string $normalizedVersion The normalized version
     *
     * @return bool
     */
    protected function satisfyStability(Link $require, $normalizedVersion)
    {
        $requireStability = $this->getRequireStability($require);
        $stability = $this->versionParser->parseStability($normalizedVersion);
        $stability = false !== strpos($normalizedVersion, '-patch') ? 'dev' : $stability;

        return Package::$stabilities[$stability] <= Package::$stabilities[$requireStability];
    }

    /**
     * Get the minimum stability for the require dependency defined in root package.
     *
     * @param Link $require The require link defined in root package.
     *
     * @return string The minimum stability
     */
    protected function getRequireStability(Link $require)
    {
        $prettyConstraint = $require->getPrettyConstraint();
        $stabilities = Package::$stabilities;

        if (preg_match_all('/@('.implode('|', array_keys($stabilities)).')/', $prettyConstraint, $matches)) {
            return $this->findInlineStabilities($matches[1]);
        }

        return $this->package->getMinimumStability();
    }

    /**
     * Find the lowest stability.
     *
     * @param string[] $stabilities The list of stability
     *
     * @return string The lowest stability
     */
    protected function findInlineStabilities(array $stabilities)
    {
        $lowestStability = 'stable';

        foreach ($stabilities as $stability) {
            $stability = $this->versionParser->normalizeStability($stability);
            $stability = $this->versionParser->parseStability($stability);

            if (Package::$stabilities[$stability] > Package::$stabilities[$lowestStability]) {
                $lowestStability = $stability;
            }
        }

        return $lowestStability;
    }

    /**
     * Get the link constraint of normalized version.
     *
     * @param string $normalizedVersion The normalized version
     *
     * @return LinkConstraintInterface The constraint
     */
    protected function getVersionConstraint($normalizedVersion)
    {
        if (preg_match('/^\d+(\.\d+)(\.\d+)(\.\d+)\-[A-Za-z0-9]+$/', $normalizedVersion)) {
            $normalizedVersion = substr($normalizedVersion, 0, strpos($normalizedVersion, '-'));
        }

        return $this->versionParser->parseConstraints($normalizedVersion);
    }

    /**
     * Initialize.
     */
    protected function initialize()
    {
        $this->requires = array_merge(
            $this->package->getRequires(),
            $this->package->getDevRequires()
        );
    }
}
