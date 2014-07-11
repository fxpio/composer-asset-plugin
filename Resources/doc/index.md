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
