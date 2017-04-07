Basic Usage
===========

1. [Installation](index.md)
2. [Composer Schema of Asset](schema.md)
3. [FAQs](faqs.md)

## Installation

See the [Release Notes](https://github.com/fxpio/composer-asset-plugin/releases)
to know the Composer version required.

### Global scope (per user) installation

```shell
$ composer global require "fxp/composer-asset-plugin:~1.3"
```

### Project scope installation

The installation in the project scope is not supported (see the
[issue #7](https://github.com/fxpio/composer-asset-plugin/issues/7)).

## Usage

### Usage with asset repository

Adding a dependency on an asset, you must add the asset to the property
`require` of the `composer.json` of your project.

It must be prefixed with `{asset-type}-asset/`.

**Example for twitter bootstrap:**

```json
{
    "require": {
        "npm-asset/bootstrap": "dev-master"
    }
}
```

**or:**

```json
{
    "require": {
        "bower-asset/bootstrap": "dev-master"
    }
}
```

### Usage with Private Bower Registry

You can work with your private Bower server build with
[Hacklone Private Bower](https://github.com/Hacklone/private-bower):

Adding the URL to your Private Bower Server in the `composer.json` in the section `config`. This
Asset Plugin automaticly look if there is a private Bower URL defined and search for your Private
Bower Package.

**Example:**

```json
{
    "config": {
        "fxp-asset": {
            "private-bower-registries": {
                "<YourPrivateBowerRegistryServerName>": "https://<YourPrivateBowerRegistryServerURL>/packages"
            }
        }
    }
}
```

### Usage with VCS repository

If your asset is not listed on the NPM- or Bower-Repository, or it is a private package, you can
create a VCS repository for it. The repository must have an asset package file for NPM (`package.json`)
or Bower (`bower.json`).

In addition, the repository must respect the specifications of [Bower Spec]
(https://github.com/bower/bower.json-spec) or [NPM Spec](https://docs.npmjs.com/files/package.json)
for the package files. Concerning the version numbers and the tags, they must respect the [Semver 2.0]
(http://semver.org/) format.

If your repository does not contain a tag that repsent the number, you must put the flag `@dev` or directly
use the development branch `dev-master`.

**Example:**

Add the following to your `composer.json`:

```json
{
    "config": {
        "fxp-asset": {
            "repositories": [
                {
                    "type": "bower-vcs",
                    "url": "https://github.com/vendor/exemple-asset-name.git"
                }
            ]
        }
    }
}
```

**Availables drivers:**

| Drivers             | NPM                 | Bower                 |
|---------------------|---------------------|-----------------------|
| **auto**            | `npm-vcs`           | `bower-vcs`           |
| Git                 | `npm-git`           | `bower-git`           |
| GitHub              | `npm-github`        | `bower-github`        |
| Git Bitbucket       | `npm-git-bitbucket` | `bower-git-bitbucket` |
| Mercurial           | `npm-hg`            | `bower-hg`            |
| Mercurial Bitbucket | `npm-hg-bitbucket`  | `bower-hg-bitbucket`  |
| SVN                 | `npm-svn`           | `bower-svn`           |
| Perforce            | `npm-perforce`      | `bower-perforce`      |

### Overriding the config of a VCS Repository

If you must use a repository other than that indicated by the registry of NPM or Bower,
you must specify the name of the package with the asset prefix in the config of the VCS
Repository.

**Example:**

```json
{
    "config": {
        "fxp-asset": {
            "repositories": [
                {
                    "type": "bower-vcs",
                    "url": "https://github.com/vendor/exemple-asset-name.git",
                    "name": "bower-asset/exemple-asset-name"
                }
            ]
        }
    }
}
```

You can also use the standard format of Composer for naming your VCS Repository:

```json
{
    "config": {
        "fxp-asset": {
            "repositories": {
                "bower-asset/exemple-asset-name": {
                    "type": "bower-vcs",
                    "url": "https://github.com/vendor/exemple-asset-name.git"
                }
            }
        }
    }
}
```

### Usage with multiple versions of the same dependency

If you need to use multiple versions of the same asset, you can do this by
simply adding a version number after the package name, separated with the "-"
character.

**Example with Jquery:**

```json
{
    "require": {
        "bower-asset/jquery": "1.11.*",
        "bower-asset/jquery-2.0.x": "2.0.x",
        "bower-asset/jquery-2.1.0": "2.1.0"
    }
}
```

The dependencies will then be placed in the following directories:

- `vendor/bower-asset/jquery` for `1.11.*`
- `vendor/bower-asset/jquery-2.0.x` for `2.0.x`
- `vendor/bower-asset/jquery-2.1.0` for `2.1.0`

### Reduce the number of requests for getting the package definitions

The root Composer package has a feature: all asset dependencies added will have automatically
a filter applied, before the importation of the branches and the tags.

In this way, all versions are not accepted by the constraint of version and they will be
skipped to the importation, and will not be injected in the `Pool`. Of course, all constraints
of versions are functional (exact version, range, wildcard, tilde operator).

**For example:**

The root `composer.json`:

```json
{
    "minimum-stability": "dev",
    "require": {
        "npm-asset/example-asset1": ">=1.0@stable",
        "npm-asset/example-asset2": ">=2.3@RC",
        "npm-asset/example-asset3": ">=1.3@beta",
        "npm-asset/example-asset4": "~0.9@alpha",
        "npm-asset/example-asset4": "2.1.*",
    }
}
```

In case you have an dependency that that requires a sub asset dependency, and given that this
optimization cannot be performed with the sub dependencies, you can add this asset dependency
directly to the root Composer package, in the same way that if you wanted to use a
well-defined version of this dependency.

### Disable the import filter using the installed packages

By default, and for dramatically optimize performance for the `update`, the plugin filters the
imports of definitions packages. In addition to filter with the dependencies in the root
Composer package, the plugin filters the imports of packages definitions with the previous
versions of the packages installed.

However it may happen that Composer throws an exception, indicating that it can not find a
compatible version. This happens if a dependency uses a new version lower than the installed
version.

Of course, several solutions can work around the problem (see the [FAQs]
(faqs.md#composer-throws-an-exception-stating-that-the-version-does-not-exist)), but the
solution below may be used in another use case.

You can disable the import filter using the versions of installed packages with the option
`config.fxp-asset.optimize-with-installed-packages` in the root Composer package:

```json
{
    "config": {
        "fxp-asset": {
            "optimize-with-installed-packages": false
        }
    }
}
```

#### Change/Disable the skip of versions by pattern

By default, the plugin does not import the `patch` versions for increase dramatically
performance. However, it is possible to change the pattern or to disable this feature.

**Example for change the pattern:**

```json
{
    "config": {
        "fxp-asset": {
            "pattern-skip-version": "(-build)"
        }
    }
}
```

**Example for disable the pattern:**

```json
{
    "config": {
        "fxp-asset": {
            "pattern-skip-version": false
        }
    }
}
```

#### Disable the conjunctive option of the import filter

You can disable the `conjunctive` mode of the import filter with the option
`config.fxp-asset.optimize-with-conjunctive` in the root Composer package:

```json
{
    "config": {
        "fxp-asset": {
            "optimize-with-conjunctive": false
        }
    }
}
```

> **Note:**
>
> This option is used only if the optimization with the installed packages is enabled

### Define a custom directory for the assets installation

By default, the plugin will install all the assets in the directory
`vendors/{asset-type}-asset` and packages will be installed in each folder with
their asset name.

But you can change the installation directory of the assets directly in the root
`composer.json`-file of your project:

```json
{
    "config": {
        "fxp-asset": {
            "installer-paths": {
                "npm-asset-library": "web/assets/vendor",
                "bower-asset-library": "web/assets/vendor"
            }
        }
    }
}
```

> **Note:**
>
> For Bower, all files defined in the section `ignore` will not be installed

### Disable or replace the deleting of the ignore files for Bower

For Bower, all files defined in the section `ignore` will be delete just after the
installation of each package. Of course, this behavior can be disabled or replaced.

**Example for disable the list of ignored files:**

```json
{
    "config": {
        "fxp-asset": {
            "ignore-files": {
                "bower-asset/example-asset1": false
            }
        }
    }
}
```

**Example for replace the list of ignored files:**

```json
{
    "config": {
        "fxp-asset": {
            "ignore-files": {
                "bower-asset/example-asset1": [
                    ".*",
                    "*.md",
                    "test"
                ]
            }
        }
    }
}
```

### Enable manually the deleting of the ignore files for NPM

For NPM, there is no section `ignore`, but you can manually add the patterns for
delete the files:

```json
{
    "config": {
        "fxp-asset": {
            "ignore-files": {
                "npm-asset/example-asset1": [
                    ".*",
                    "*.md",
                    "test"
                ]
            }
        }
    }
}
```

### Work with the NPM scopes

NPM can manage the package with the vendor scopes (`@<vendor>/<dependency-name>`),
but Composer has already a namespace for vendors, and this plugin create a virtual
vendor for the NPM assets (`npm-asset/`). Futhermore, the `@` character is not
managed by Composer for the package name.

For this reason, the NPM scope `@<vendor>/` is converted into `<vendor>--`.

NPM package name              | Composer package name
------------------------------|-----------------------------------------
`@<vendor>/<dependency-name>` | `npm-asset/<vendor>--<dependency-name>`

### Use the Ignore Files Manager in the Composer scripts

Sometimes you need to clean a package that is not considered an NPM/Bower Asset
Package. To do this, you can use the script helper
`Fxp\Composer\AssetPlugin\Composer\ScriptHandler::deleteIgnoredFiles` for the
`post-package-install` or `post-package-update` script events.

**Example:**

```json
{
    "scripts": {
        "post-package-install": [
            "Fxp\\Composer\\AssetPlugin\\Composer\\ScriptHandler::deleteIgnoredFiles"
        ],
        "post-package-update": [
            "Fxp\\Composer\\AssetPlugin\\Composer\\ScriptHandler::deleteIgnoredFiles"
        ]
    },
    "config": {
        "fxp-asset": {
            "ignore-files": {
                "acme/other-asset": [
                    ".*",
                    "*.md",
                    "test"
                ]
            }
        }
    }
}
```

### Override the main files for Bower

The bower.json specification allows packages to define entry-point files
which can later be processed with taskrunners or build scripts. Some Bower
plugins like main-bower-files, wiredep and asset-builder have a feature to
override the package main files in the project configuration file.

You can do the same with composer-asset-plugin, just add a section
`config.fxp-asset.main-files` in the root project `composer.json` file with the package
name and the files you want to mark as main files.

**Example:**

```json
{
    "config": {
        "fxp-asset": {
            "main-files": {
                "acme/other-asset": [
                    "other-asset.js"
                ]
            }
        }
    }
}
```

### Disable the search for an asset registry

If you want to disable the search for an asset registry, you can add the
option `config.fxp-asset.registry-options.{type}-searchable` in the root project
`composer.json`-file.

**Example:**

```json
{
    "config": {
        "fxp-asset": {
            "registry-options": {
                "npm-searchable": false,
                "bower-searchable": false
            }
        }
    }
}
```

### Use no-api option of VCS Githhub driver

If you want to use the [no-api](https://getcomposer.org/doc/05-repositories.md#git-alternatives) option
for your Github assets, you can add the option `config.fxp-asset.vcs-driver-options.github-no-api` in
the root project `composer.json` file. By default, this option is to `false`. The option `config.fxp-asset.pattern-skip-version`
can be used to exclude tags via a regular expression.

```json
{
    "config": {
        "fxp-asset": {
            "vcs-driver-options": {
                "github-no-api": true
            },
            "pattern-skip-version": "(-build|-patch)"
        }
    }
}
```

You can further define this option for each package:

```json
{
    "config": {
        "fxp-asset": {
            "vcs-driver-options": {
                "github-no-api": {
                    "default": true,
                    "packages": {
                        "bower-asset/example-asset1": false
                    }
                }
            }
        }
    }
}
```

With this configuration, all your github packages will use the native Git, except for
the `bower-asset/example-asset1` package.

### Solve the conflicts of asset dependencies

Bower include a [resolution section](https://jaketrent.com/post/bower-resolutions) to
solve the conflicts between 2 same dependencies but with different versions.

As for NPM, it's possible to install several versions of the same dependency by different
dependencies, which is not the case for Bower and Composer. Only the installation of a
single version compatible for all dependencies is possible.

The dependency resolution would force (replace) a version or range version directly in the
root Composer package.

**Example:**
```json
    "name": "foo/bar",
    "require": {
        "bower-asset/jquery": "^2.2.0"
    }
```
```json
    "name": "bar/baz",
    "require": {
        "bower-asset/jquery": "2.0.*"
    }
```
```json
    "name": "root/package",
    "require": {
        "foo/bar": "^1.0.0",
        "bar/baz": "^1.0.0"
    }
    "config": {
        "fxp-asset": {
            "resolutions": {
                "bower-asset/jquery": "^3.0.0"
            }
        }
    }
```

Result, all asset packages with the `bower-asset/jquery` dependency will use the `^3.0.0` range version.

> **Note:**
> Be careful when replacing the version, and check the compatibility before.

### Define the config for all projects

You can define each option (`config.fxp-asset.*`) in each project in the `composer.json`
file of each project, but you can also set an option for all projects.

To do this, you simply need to add your options in the Composer global configuration,
in the file of your choice:

- `<COMPOSER_HOME>/composer.json` file
- `<COMPOSER_HOME>/config.json` file

> **Note:**
> The `composer global config` command cannot be used, bacause Composer does not accept custom options.
> But you can use the command `composer global config -e` to edit the global `composer.json`
file with your text editor.

### Define the config in a environment variable

You can define each option (`config.fxp-asset.*`) directly in the PHP environment variables. For
this, all variables will start with `FXP_ASSET__` and uppercased, and each `-` will replaced by `_`.

The accepted value types are:

- string
- boolean
- integer
- JSON array or object

**Example:**
```json
{
    "config": {
        "fxp-asset": {
            "pattern-skip-version": "(-patch)"
        }
    }
}
```

Can be overridden by `FXP_ASSET__PATTERN_SKIP_VERSION="(-build)"` environment variable.

**Example:**
```json
{
    "config": {
        "fxp-asset": {
            "vcs-driver-options": {
                "github-no-api": true
            }
        }
    }
}
```

Can be overridden by `FXP_ASSET__VCS_DRIVER_OPTIONS='{"github-no-api": true}'` environment variable.


### Config priority order

The config values are retrieved in priority in:

1. the environment variables starting with `FXP_ASSET__`
2. the project `composer.json` file
3. the global `<COMPOSER_HOME>/config.json` file
4. the global `<COMPOSER_HOME>/composer.json` file
5. the deprecated config `extra.asset-*` of the project `composer.json` file

### Disable the plugin

When you working on multiple PHP projects, you do not necessarily need to use the plugin. It's
possible to disable the plugin with the `config.fxp-asset.enabled` option with the `false` value.

For example, you can disable the plugin globally (for all your projects), and enable it only
for projects requiring the plugin.

**Example:**
```json
// The global <COMPOSER_HOME>/config.json file
{
    "config": {
        "fxp-asset": {
            "enabled": false
        }
    }
}
```
```json
// The project composer.json file
{
    "config": {
        "fxp-asset": {
            "enabled": true
        }
    }
}
```

> **Note:**
> If you disable the plugin, and your project require this plugin, Composer will throw an exception indicating that the asset dependencies does not exist.
