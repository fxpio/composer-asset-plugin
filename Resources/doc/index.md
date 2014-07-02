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

Example for twitter bootstrap:

```json
{
    "require": {
        "npm-asset/bootstrap": "dev-master"
    }
}
```

or

```json
{
    "require": {
        "bower-asset/bootstrap": "dev-master"
    }
}
```

### Usage with VCS repository

If your asset is not listed on NPM and Bower, or that it is private, you can
create VCS repository.

Example:

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
