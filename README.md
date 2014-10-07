NPM/Bower Dependency Manager for Composer
=========================================

[![Latest Stable Version](https://poser.pugx.org/fxp/composer-asset-plugin/v/stable.svg)](https://packagist.org/packages/fxp/composer-asset-plugin)
[![Latest Unstable Version](https://poser.pugx.org/fxp/composer-asset-plugin/v/unstable.svg)](https://packagist.org/packages/fxp/composer-asset-plugin)
[![Build Status](https://travis-ci.org/francoispluchino/composer-asset-plugin.svg?branch=master)](https://travis-ci.org/francoispluchino/composer-asset-plugin)
[![Coverage Status](https://coveralls.io/repos/francoispluchino/composer-asset-plugin/badge.png)](https://coveralls.io/r/francoispluchino/composer-asset-plugin)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/francoispluchino/composer-asset-plugin/badges/quality-score.png)](https://scrutinizer-ci.com/g/francoispluchino/composer-asset-plugin)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/0d67ca33-5a72-46b8-b109-cfbf95673fce/mini.png)](https://insight.sensiolabs.com/projects/0d67ca33-5a72-46b8-b109-cfbf95673fce)

The Composer Asset Plugin allows you to manage project assets (css, js, etc.) in your `composer.json`
without installing NPM or Bower.

This plugin works by transposing package information from NPM or Bower to a compatible version for Composer.
This allows you to manage asset dependencies in a PHP based project very easily.

> **Warning!**
>
> Currently the plugin only works in "global" mode, the PR [#3082](https://github.com/composer/composer/pull/3082)
> will allow the plugin to work in "project" mode


##### Features include:

- Works with native management system versions of VCS repository of composer
- Works with public and private VCS repositories
- Lazy load asset package files in VCS repositories to improve performance
- Automatically get and create an Asset VCS repository defined in:
  - [NPM Registry](https://www.npmjs.org)
  - [Bower Registry](http://bower.io/search)
- Automatically get and create the Asset VCS repositories of dependencies defined
  in each asset package (dev dependencies included)
- Mapping conversion of asset package to composer package for:
  - [NPM Package](https://www.npmjs.org/doc/package.json.html) - [package.json](Resources/doc/schema.md#npm-mapping)
  - [Bower Package](http://bower.io/docs/creating-packages) - [bower.json](Resources/doc/schema.md#bower-mapping)
- Conversion of [Semver version](Resources/doc/schema.md#verison-conversion) to the composer version
- Conversion of [Semver range version](Resources/doc/schema.md#range-verison-conversion) to the composer range version
- Conversion of [dependencies with URL](Resources/doc/schema.md#url-range-verison-conversion) to the composer dependencies with the creation of VCS repositories
- Conversion of [multiple versions of the same dependency](Resources/doc/schema.md#multiple-version-of-depdendency-in-the-same-project) to different dependencies of composer
- Add manually the [multiple versions of a same dependency in the project](Resources/doc/index.md#usage-with-multiple-version-of-a-same-dependency)
- Add a [custom config of VCS Repository](Resources/doc/index.md#usage-with-vcs-repository)
- Override the [config of VCS Repository](Resources/doc/index.md#overriding-the-config-of-a-vcs-repository) defined by the asset registry directly in extra section of root composer
- VCS drivers for:
  - [Git](Resources/doc/index.md#usage-with-vcs-repository)
  - [GitHub](Resources/doc/index.md#usage-with-vcs-repository) (compatible with repository redirects)
  - [Git Bitbucket](Resources/doc/index.md#usage-with-vcs-repository)
  - [Mercurial](Resources/doc/index.md#usage-with-vcs-repository)
  - [Mercurial Bitbucket](Resources/doc/index.md#usage-with-vcs-repository)
  - [SVN](Resources/doc/index.md#usage-with-vcs-repository)
  - [Perforce](Resources/doc/index.md#usage-with-vcs-repository)
- Local cache system for:
  - package versions
  - package contents
  - repository redirects
- Custom asset installers configurable in the root file `composer.json`
- For Bower, all files defined in the section `ignore` will not be installed
- Disable or replace the deleting of the ignore files for Bower
- Enable manually the deleting of the ignore files for NPM
- Use the Ignore Files Manager in the Composer scripts
- Compatible with commands:
  - `search` (bower only)
  - `show`
  - `licenses`
  - `status`

##### Why this plugin?

There already are several possibilities for managing assets in a PHP project:

1. Install Node.js and use NPM or Bower command line in addition to Composer command line
2. Do #1, but add Composer scripts to automate the process
3. Include assets directly in the project (not recommended)
4. Create a repository with all assets and include the `composer.json` file (and use Packagist or an VCS Repository)
5. Add a package repository in `composer.json` with a direct download link
6. Create a Satis or Packagist server
7. Other?

It goes without saying that each javascript, CSS, etc. library should be developed with the usual tools for that
language, which front-end developers know well. However, in the case of a complete project in PHP, it shouldn't 
be necessary to use several tools (PHP, Nodejs, Composer, NPM, Bower, Grunt, etc.) to simply install
these assets in your project.

This plugin has been created to address these issues. Additionally, most developers will not add a `composer.json`
file to their projects just to support php based projects, especially when npm and/or bower already exist and are
widely used.

Documentation
-------------

The bulk of the documentation is located in `Resources/doc/index.md`:

[Read the Documentation](Resources/doc/index.md)

[Read the FAQs](Resources/doc/faqs.md)

Installation
------------

All the installation instructions are located in [documentation](Resources/doc/index.md).

License
-------

This composer plugin is under the MIT license. See the complete license in:

[Resources/meta/LICENSE](Resources/meta/LICENSE)

About
-----

Fxp Composer Asset Plugin is a [François Pluchino](https://github.com/francoispluchino) initiative.
See also the list of [contributors](https://github.com/francoispluchino/composer-asset-plugin/contributors).

Reporting an issue or a feature request
---------------------------------------

Issues and feature requests are tracked in the [Github issue tracker](https://github.com/francoispluchino/composer-asset-plugin/issues).
