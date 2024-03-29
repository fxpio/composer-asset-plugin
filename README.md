NPM/Bower Dependency Manager for Composer
=========================================

[![Latest Version](https://img.shields.io/packagist/v/fxp/composer-asset-plugin.svg)](https://packagist.org/packages/fxp/composer-asset-plugin)
[![Build Status](https://img.shields.io/travis/com/fxpio/composer-asset-plugin.svg)](https://travis-ci.com/fxpio/composer-asset-plugin)
[![Coverage Status](https://img.shields.io/coveralls/fxpio/composer-asset-plugin.svg)](https://coveralls.io/r/fxpio/composer-asset-plugin)
[![SymfonyInsight](https://img.shields.io/symfony/i/grade/0d67ca33-5a72-46b8-b109-cfbf95673fce.svg)](https://insight.symfony.com/projects/0d67ca33-5a72-46b8-b109-cfbf95673fce)
[![Packagist Downloads](https://img.shields.io/packagist/dt/fxp/composer-asset-plugin.svg)](https://packagist.org/packages/fxp/composer-asset-plugin/stats)

The Composer Asset Plugin allows you to manage project assets (css, js, etc.) in your `composer.json`
without installing NPM or Bower.

This plugin works by transposing package information from NPM or Bower to a compatible version for Composer.
This allows you to manage asset dependencies in a PHP based project very easily.

> **Important:**
>
> The next major version of Composer Asset Plugin is so different, but also incompatible with the current version,
> that it became a new project named [Foxy](https://github.com/fxpio/foxy).
>
> Foxy is the new way to manage the assets of PHP libraries, because it works nativelly with all the features of
> NPM or Yarn. However, this plugin will continue to be maintained by the community, without having new features.
>
> You can read [the reasons for this new version](https://github.com/yiisoft/yii2/issues/14297#issuecomment-327565136),
> or [the difference between Foxy and Fxp Composer Asset Plugin](https://github.com/fxpio/foxy/blob/master/Resources/doc/faqs.md#what-is-the-difference-between-foxy-and-fxp-composer-asset-plugin),
> but also [how does Foxy work](https://github.com/fxpio/foxy/blob/master/Resources/doc/faqs.md#how-does-the-plugin-work).

##### Features include:

- Works with native management system versions of VCS repository of composer
- Works with public and private VCS repositories
- Lazy loader of asset package definitions to improve performance
- Import filter with the dependencies of the root package and the installed packages, for increased dramatically the performance for the update
- Automatically get and create an Asset VCS repository defined in:
  - [NPM Registry](https://www.npmjs.org)
  - [Bower Registry](http://bower.io/search)
  - [Private Bower Registry](https://github.com/Hacklone/private-bower)
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
- Override the [config of VCS Repository](Resources/doc/index.md#overriding-the-config-of-a-vcs-repository) defined by the asset registry directly in config section of root composer
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
- Configure the plugin per project, globally or with the environment variables
- Compatible with all commands, including:
  - `depends`
  - `diagnose`
  - `licenses`
  - `remove`
  - `require`
  - `search` (bower only)
  - `show`
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

[Read the Release Notes](https://github.com/fxpio/composer-asset-plugin/releases)

Installation
------------

All the installation instructions are located in [documentation](Resources/doc/index.md).

License
-------

This composer plugin is under the MIT license. See the complete license in:

[LICENSE](LICENSE)

About
-----

Fxp Composer Asset Plugin is a [François Pluchino](https://github.com/francoispluchino) initiative.
See also the list of [contributors](https://github.com/fxpio/composer-asset-plugin/contributors).

Reporting an issue or a feature request
---------------------------------------

Issues and feature requests are tracked in the [Github issue tracker](https://github.com/fxpio/composer-asset-plugin/issues).
