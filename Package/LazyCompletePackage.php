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

use Composer\Package\CompletePackage;
use Fxp\Composer\AssetPlugin\Package\Loader\LazyLoaderInterface;

/**
 * The lazy loading complete package.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class LazyCompletePackage extends CompletePackage implements LazyPackageInterface
{
    /**
     * @var LazyLoaderInterface
     */
    protected $lazyLoader;

    /**
     * {@inheritDoc}
     */
    public function getTransportOptions()
    {
        $this->initialize();

        return parent::getTransportOptions();
    }

    /**
     * {@inheritDoc}
     */
    public function getTargetDir()
    {
        $this->initialize();

        return parent::getTargetDir();
    }

    /**
     * {@inheritDoc}
     */
    public function getExtra()
    {
        $this->initialize();

        return parent::getExtra();
    }

    /**
     * {@inheritDoc}
     */
    public function getBinaries()
    {
        $this->initialize();

        return parent::getBinaries();
    }

    /**
     * {@inheritDoc}
     */
    public function getInstallationSource()
    {
        $this->initialize();

        return parent::getInstallationSource();
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceType()
    {
        $this->initialize();

        return parent::getSourceType();
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceUrl()
    {
        $this->initialize();

        return parent::getSourceUrl();
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceReference()
    {
        $this->initialize();

        return parent::getSourceReference();
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceMirrors()
    {
        $this->initialize();

        return parent::getSourceMirrors();
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceUrls()
    {
        $this->initialize();

        return parent::getSourceUrls();
    }

    /**
     * {@inheritDoc}
     */
    public function getDistType()
    {
        $this->initialize();

        return parent::getDistType();
    }

    /**
     * {@inheritDoc}
     */
    public function getDistUrl()
    {
        $this->initialize();

        return parent::getDistUrl();
    }

    /**
     * {@inheritDoc}
     */
    public function getDistReference()
    {
        $this->initialize();

        return parent::getDistReference();
    }

    /**
     * {@inheritDoc}
     */
    public function getDistSha1Checksum()
    {
        $this->initialize();

        return parent::getDistSha1Checksum();
    }

    /**
     * {@inheritDoc}
     */
    public function getDistMirrors()
    {
        $this->initialize();

        return parent::getDistMirrors();
    }

    /**
     * {@inheritDoc}
     */
    public function getDistUrls()
    {
        $this->initialize();

        return parent::getDistUrls();
    }

    /**
     * {@inheritDoc}
     */
    public function getReleaseDate()
    {
        $this->initialize();

        return parent::getReleaseDate();
    }

    /**
     * {@inheritDoc}
     */
    public function getRequires()
    {
        $this->initialize();

        return parent::getRequires();
    }

    /**
     * {@inheritDoc}
     */
    public function getConflicts()
    {
        $this->initialize();

        return parent::getConflicts();
    }

    /**
     * {@inheritDoc}
     */
    public function getDevRequires()
    {
        $this->initialize();

        return parent::getDevRequires();
    }

    /**
     * {@inheritDoc}
     */
    public function getSuggests()
    {
        $this->initialize();

        return parent::getSuggests();
    }

    /**
     * {@inheritDoc}
     */
    public function getAutoload()
    {
        $this->initialize();

        return parent::getAutoload();
    }

    /**
     * {@inheritDoc}
     */
    public function getDevAutoload()
    {
        $this->initialize();

        return parent::getDevAutoload();
    }

    /**
     * {@inheritDoc}
     */
    public function getIncludePaths()
    {
        $this->initialize();

        return parent::getIncludePaths();
    }

    /**
     * {@inheritDoc}
     */
    public function getNotificationUrl()
    {
        $this->initialize();

        return parent::getNotificationUrl();
    }

    /**
     * {@inheritDoc}
     */
    public function getArchiveExcludes()
    {
        $this->initialize();

        return parent::getArchiveExcludes();
    }

    /**
     * {@inheritDoc}
     */
    public function getScripts()
    {
        $this->initialize();

        return parent::getScripts();
    }

    /**
     * {@inheritDoc}
     */
    public function getRepositories()
    {
        $this->initialize();

        return parent::getRepositories();
    }

    /**
     * {@inheritDoc}
     */
    public function getLicense()
    {
        $this->initialize();

        return parent::getLicense();
    }

    /**
     * {@inheritDoc}
     */
    public function getKeywords()
    {
        $this->initialize();

        return parent::getKeywords();
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthors()
    {
        $this->initialize();

        return parent::getAuthors();
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {
        $this->initialize();

        return parent::getDescription();
    }

    /**
     * {@inheritDoc}
     */
    public function getHomepage()
    {
        $this->initialize();

        return parent::getHomepage();
    }

    /**
     * {@inheritDoc}
     */
    public function getSupport()
    {
        $this->initialize();

        return parent::getSupport();
    }

    /**
     * {@inheritDoc}
     */
    public function setLoader(LazyLoaderInterface $lazyLoader)
    {
        $this->lazyLoader = $lazyLoader;
    }

    /**
     * Initialize the package.
     */
    protected function initialize()
    {
        if (!$this->lazyLoader) {
            return;
        }

        $real = $this->lazyLoader->load($this);
        $this->lazyLoader = null;

        if (false === $real) {
            return;
        }

        $this->type = $real->getType();
        $this->transportOptions = $real->getTransportOptions();
        $this->targetDir = $real->getTargetDir();
        $this->extra = $real->getExtra();
        $this->binaries = $real->getBinaries();
        $this->installationSource = $real->getInstallationSource();
        $this->sourceType = $real->getSourceType();
        $this->sourceUrl = $real->getSourceUrl();
        $this->sourceReference = $real->getSourceReference();
        $this->sourceMirrors = $real->getSourceMirrors();
        $this->distType = $real->getDistType();
        $this->distUrl = $real->getDistUrl();
        $this->distReference = $real->getDistReference();
        $this->distSha1Checksum = $real->getDistSha1Checksum();
        $this->distMirrors = $real->getDistMirrors();
        $this->releaseDate = $real->getReleaseDate();
        $this->requires = $real->getRequires();
        $this->conflicts = $real->getConflicts();
        $this->provides = $real->getProvides();
        $this->replaces = $real->getReplaces();
        $this->devRequires = $real->getDevRequires();
        $this->suggests = $real->getSuggests();
        $this->autoload = $real->getAutoload();
        $this->devAutoload = $real->getDevAutoload();
        $this->includePaths = $real->getIncludePaths();
        $this->notificationUrl = $real->getNotificationUrl();
        $this->archiveExcludes = $real->getArchiveExcludes();
        $this->scripts = $real->getScripts();
        $this->repositories = $real->getRepositories();
        $this->license = $real->getLicense();
        $this->keywords = $real->getKeywords();
        $this->authors = $real->getAuthors();
        $this->description = $real->getDescription();
        $this->homepage = $real->getHomepage();
        $this->support = $real->getSupport();
    }
}
