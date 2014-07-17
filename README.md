NPM/Bower Dependency Manager for Composer
=========================================

[![Latest Stable Version](https://poser.pugx.org/fxp/composer-asset-plugin/v/stable.svg)](https://packagist.org/packages/fxp/composer-asset-plugin)
[![Latest Unstable Version](https://poser.pugx.org/fxp/composer-asset-plugin/v/unstable.svg)](https://packagist.org/packages/fxp/composer-asset-plugin)
[![Build Status](https://travis-ci.org/francoispluchino/composer-asset-plugin.svg?branch=master)](https://travis-ci.org/francoispluchino/composer-asset-plugin)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/francoispluchino/composer-asset-plugin/badges/quality-score.png)](https://scrutinizer-ci.com/g/francoispluchino/composer-asset-plugin)
[![Code Coverage](https://scrutinizer-ci.com/g/francoispluchino/composer-asset-plugin/badges/coverage.png)](https://scrutinizer-ci.com/g/francoispluchino/composer-asset-plugin)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/0d67ca33-5a72-46b8-b109-cfbf95673fce/mini.png)](https://insight.sensiolabs.com/projects/0d67ca33-5a72-46b8-b109-cfbf95673fce)

The Composer Asset Plugin allows you to manage project assets (css,js, etc) in your composer.json
without installing NPM or Bower.

This plugin works by transposing package information from NPM or Bower to a compatible version for Composer.
This allows you to manage asset dependencies in a PHP based project much more easily.

> **Warning!**
>
> Currently the plugin only works in "global" mode, the PR [#3082](https://github.com/composer/composer/pull/3082)
> will allow the plugin in "project" mode


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
- VCS drivers for:
  - Git
  - GitHub
- Local cache system for:
  - package versions
  - package contents
- Custom asset installers configurable in the root file `composer.json`
- For Bower, all files defined in the section `ignore` will not be installed
- Compatible with commands:
  - `search` (bower only)
  - `show`
  - `licenses`
  - `status`

##### Why this plugin?

Currently, for
manage dependencies of javascript asset in a project PHP, we have several possibilities:

1. Install Node.js and use NPM or Bower command line in addition to Composer command line
2. Do the solution 1, but add the Composer scripts to automate the process
3. Include assets directly in the project (really not recommended)
4. Create a repository with all assets and include the `composer.json` file (and use Packagist or an VCS Repository)
5. Adds Package Repository in `composer.json` with the direct download link
6. Creates a Satis or Packagist server
7. Other?

It goes without saying that each javascript, css, etc library should be developed with the usual tools for that
language, which front-end developers know well. However, in the case of a complete project in PHP, it is
shouldn't be necessary to use several tools (php, nodejs, composer, npm, bower, grunt, etc...) to simply install
these assets in your project.

It is in this context that this plugin was created: Not all (or many) will add a composer.json file to their
projects simply to support php based projects, especially when npm and/or bower already exist and are
widely used.

Documentation
-------------

The bulk of the documentation is stored in the `Resources/doc/index.md`
file in this bundle:

[Read the Documentation](Resources/doc/index.md)

Installation
------------

All the installation instructions are located in [documentation](Resources/doc/index.md).

License
-------

This project is under the MIT license. See the complete license in:

[Resources/meta/LICENSE](Resources/meta/LICENSE)

About
-----

Fxp Composer Asset Plugin is a [François Pluchino](https://github.com/francoispluchino) initiative.
See also the list of [contributors](https://github.com/francoispluchino/composer-asset-plugin/contributors).

Reporting an issue or a feature request
---------------------------------------

Issues and feature requests are tracked in the [Github issue tracker](https://github.com/francoispluchino/composer-asset-plugin/issues).
