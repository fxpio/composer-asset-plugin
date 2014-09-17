Basic Usage
===========

1. [Installation](index.md)
2. [Composer Schema of Asset](schema.md)

## Installation

### Global scope (per user) installation

```shell
$ composer global require "fxp/composer-asset-plugin:~1.0"
```

### Project scope installation (will work with the PR [#3082](https://github.com/composer/composer/pull/3082))

```shell
$ composer require "fxp/composer-asset-plugin:~1.0"
```

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

### Usage with VCS repository

If your asset is not listed on NPM and Bower, or that it is private, you can
create VCS repository. The repository must have a asset package file for NPM
and/or Bower.

**Example:**

```json
{
    "extra": {
        "asset-repositories": [
            {
                "type": "bower-vcs",
                "url": "https://github.com/vendor/exemple-asset-name.git"
            }
        ]
    }
}
```

### Usage with multiple version of a same dependency

If you need to use multiple version of the same asset, You can do this by
simply adding a version number after the package name, separated by the "-"
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

The dependencies will be placed by default in:

- `vendor/bower-asset/jquery` for `1.11.*`
- `vendor/bower-asset/jquery-2.0.x` for `2.0.x`
- `vendor/bower-asset/jquery-2.1.0` for `2.1.0`

### Disable the search for an asset registry

If you want to disable the search for an asset registry, you can add an extra
option `extra.asset-registry-options.{type}-searchable` in the root project
file `composer.json`.

**Example:**

```json
{
    "extra": {
        "asset-registry-options": {
            "npm-searchable": false,
            "bower-searchable": false
        }
    }
}
```

### Include dependencies using URL in versions

Currently, the plugin can not add automatically the VCS repositories to the `Pool`
for the dependency resolution, because the plugin system of Composer does not allow
for the moment (see [composer/composer#3116](https://github.com/composer/composer/issues/3116)).
It is therefore necessary to add manually the VCS Repositories in the root file `composer.json`.

**For example:**

The asset package:
```json
{
  "name": "example-asset1",
  "version": "1.0.0",
  "dependencies": {
    "asset2": "git@github.com:vendor/example-asset2.git#2.3.0"
  }
}
```

The root `composer.json` must have:
```json
{
    "require": {
        "npm-asset/example-asset1": "1.0"
    },
    "extra": {
        "asset-repositories": [
            {
                "type": "npm-vcs",
                "url": "git@github.com:vendor/example-asset2.git"
            }
        ]
    }
}
```

### Define a custom directory for the assets installation

By default, the plugin will install all the assets in the directory
`vendors/{asset-type}-asset` and packages will be installed in each folder with
their asset name.

But you can change the installation directory of the assets directly in the root
file `composer.json` of your project:

```json
{
    "extra": {
        "asset-installer-paths": {
            "npm-asset-library": "web/assets/vendor",
            "bower-asset-library": "web/assets/vendor"
        }
    }
}
```

> **Note:**
>
> For Bower, all files defined in the section `ignore` will not be installed
