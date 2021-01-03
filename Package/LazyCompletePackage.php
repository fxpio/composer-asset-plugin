<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Package;

/**
 * The lazy loading complete package.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class LazyCompletePackage extends AbstractLazyCompletePackage implements LazyPackageInterface
{
    public function getTransportOptions()
    {
        $this->initialize();

        return parent::getTransportOptions();
    }

    public function getTargetDir()
    {
        $this->initialize();

        return parent::getTargetDir();
    }

    public function getExtra()
    {
        $this->initialize();

        return parent::getExtra();
    }

    public function getBinaries()
    {
        $this->initialize();

        return parent::getBinaries();
    }

    public function getInstallationSource()
    {
        $this->initialize();

        return parent::getInstallationSource();
    }

    public function getSourceType()
    {
        $this->initialize();

        return parent::getSourceType();
    }

    public function getSourceUrl()
    {
        $this->initialize();

        return parent::getSourceUrl();
    }

    public function getSourceReference()
    {
        $this->initialize();

        return parent::getSourceReference();
    }

    public function getSourceMirrors()
    {
        $this->initialize();

        return parent::getSourceMirrors();
    }

    public function getSourceUrls()
    {
        $this->initialize();

        return parent::getSourceUrls();
    }

    public function getDistType()
    {
        $this->initialize();

        return parent::getDistType();
    }

    public function getDistUrl()
    {
        $this->initialize();

        return parent::getDistUrl();
    }

    public function getDistReference()
    {
        $this->initialize();

        return parent::getDistReference();
    }

    public function getDistSha1Checksum()
    {
        $this->initialize();

        return parent::getDistSha1Checksum();
    }

    public function getDistMirrors()
    {
        $this->initialize();

        return parent::getDistMirrors();
    }

    public function getDistUrls()
    {
        $this->initialize();

        return parent::getDistUrls();
    }

    public function getReleaseDate()
    {
        $this->initialize();

        return parent::getReleaseDate();
    }

    public function getRequires()
    {
        $this->initialize();

        return parent::getRequires();
    }

    public function getConflicts()
    {
        $this->initialize();

        return parent::getConflicts();
    }

    public function getDevRequires()
    {
        $this->initialize();

        return parent::getDevRequires();
    }

    public function getSuggests()
    {
        $this->initialize();

        return parent::getSuggests();
    }
}
