Fxp Composer Asset Plugin
=========================

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/0d67ca33-5a72-46b8-b109-cfbf95673fce/big.png)](https://insight.sensiolabs.com/projects/0d67ca33-5a72-46b8-b109-cfbf95673fce)

[![Latest Stable Version](https://poser.pugx.org/fxp/composer-asset-plugin/v/stable.svg)](https://packagist.org/packages/fxp/composer-asset-plugin)
[![Latest Unstable Version](https://poser.pugx.org/fxp/composer-asset-plugin/v/unstable.svg)](https://packagist.org/packages/fxp/composer-asset-plugin)
[![Build Status](https://travis-ci.org/francoispluchino/composer-asset-plugin.svg?branch=master)](https://travis-ci.org/francoispluchino/composer-asset-plugin)

> **Warning!**
>
> Currently the plugin only works in "global" mode, the PR [#3082](https://github.com/composer/composer/pull/3082)
> will work the plugin in "project" mode

The Composer Asset Plugin allows you to manage your assets with NPM or Bower package file directly in
the Composer.

This plugin is not intended to circumvent dependency managers, that are NPM or Bower, but to provide a
simple solution to include assets managed by a PHP project with Composer.

It goes without saying that each library javascript must be developed with his usual tools for this
language, that front-end developers know well. However, in the case of a complete project PHP, it is
not necessary to use several tools (php, nodejs, composer, npm, bower, grunt, etc...) to be installed.

It is in this context that this plugin was created: it's not necessary to add an `composer.json`
file and save the library on [Packagist.org](https://packagist.org/), in addition to do this for NPM or
Bower. In addition, third party libraries used regularly only supports NPM and/or Bower, and therefore it
is more difficult even impossible to add the Composer file, and this is understandable.

That is why the plugin supports only transposing the package informations of NPM or Bower, to a
compatible version for Composer, allowing a management of dependencies for a project PHP much more readily.

##### Why this plugin?

Currently, for manage dependencies of javascript asset in a project PHP, we have several possibilities:

1. Install Node.js and use NPM or Bower command line in addition to Composer command line
2. Do the solution 1, but add the Composer scripts to automate the process
3. Include assets directly in the project (really not recommended)
4. Create a repository with all assets and include the `composer.json` file (and use Packagist or an VCS Repository)
5. Adds Package Repository in `composer.json` with the direct download link
6. Creates a Satis or Packagist server
7. Other?

But none of these solutions can't simply manage the javascript assets with their dependencies directly
into Composer. This plugin allows to use the main lists of javascript deposits, but with the possibility
of keeping dependency management in Composer, with the same advantages for the management of versions.

##### Features include:

- Works with native management system versions of VCS repository of Composer for:
  - [NPM Package](https://www.npmjs.org) - package.json (public and private repository)
  - [Bower Package](http://bower.io) - bower.json (public and private repository)
- Gets and creates automatically a Asset VCS Repository defined in:
  - [NPM Registry](https://www.npmjs.org)
  - [Bower Registry](http://bower.io)
- Drivers for:
  - Git
  - GitHub
- Conversion of asset version to the composer version for:
  - NPM
  - Bower
- Caches the package versions
- Caches the package content
- Compatible with:
  - search command (bower only)
  - show command
  - licenses command
  - status command
  - dependencies (require)
  - dev dependencies (require-dev)

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

This bundle is under the MIT license. See the complete license in the bundle:

[Resources/meta/LICENSE](Resources/meta/LICENSE)

About
-----

Fxp Composer Asset Plugin is a [François Pluchino](https://github.com/francoispluchino) initiative.
See also the list of [contributors](https://github.com/francoispluchino/composer-asset-plugin/contributors).

Reporting an issue or a feature request
---------------------------------------

Issues and feature requests are tracked in the [Github issue tracker](https://github.com/francoispluchino/composer-asset-plugin/issues).
