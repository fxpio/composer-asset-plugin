Fxp Composer Asset Plugin
=========================

The Composer Asset Plugin allows you to manage your assets with NPM or Bower directly in the Composer.

To manage dependencies of javascript asset in PHP, we have several possibilities:

1. Install Node.js and use NPM or Bower command line in addition to Composer command line
2. Do the solution 1, but add the Composer scripts to automate the process
3. Include assets directly in the project (really not recommended)

But none of these solutions can't simply manage the javascript assets with their dependencies directly
into Composer. It is in this context that this plugin was developed, allowing using the main lists of
javascript deposits, but with the possibility of keeping dependency management in Composer, with the
same advantages in the management of versions.

Features include:

- Works with native management system versions of VCS repository of Composer for:
  - [NPM Package](https://www.npmjs.org) (public and private repository)
  - [Bower Package](http://bower.io) (public and private repository)
- Gets and creates automatically a Asset VCS Repository defined in:
  - [NPM Registry](https://www.npmjs.org)
  - [Bower Registry](http://bower.io)
- Drivers for:
  - Git
  - Github
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
