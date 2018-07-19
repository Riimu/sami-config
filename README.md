# Riimu's Automatic Sami Configuration #

I personally use [Sami] for generating API documentation for number of my projects. However, to generate that
documentation, I need to set up a configuration for each project that only varies slightly per project. Thus
I created this composer package that can automatically figure out the configuration and set it up properly
for each project.

Essentially, by using this automatic configuration utility, you can have Sami configured with some defaults
that are figured from rest of the project files rather than having to manually duplicate them into the
Sami config.

[![Travis](https://img.shields.io/travis/Riimu/sami-config.svg?style=flat-square)](https://travis-ci.org/Riimu/sami-config)
[![Packagist](https://img.shields.io/packagist/v/riimu/sami-config.svg?style=flat-square)](https://packagist.org/packages/riimu/sami-config)

## Usage ##

To add this configuration utility to your project, you should first include it via composer:

```
$ composer require --dev riimu/sami-config
```

Then you should create a file named `sami_config.php` in your project root directory that simply contains

```php
<?php

return require __DIR__ . '/vendor/riimu/sami-config/config.php';
```

The `sami_config.php` can now be used as the configuration for the documentation generator, e.g.

```
$ sami.php update sami_config.php
```

## Automatic Configurations ##

### Source ###

The documentation is generted from the `src` directory in the project root directory.

### Theme ###

No theme will be set for the generated documentation, but you can use environmental variable `SAMI_THEME` to
set a theme by setting it to a path to directory that contains a theme `manifest.yml`. The directory name and
the name of the theme must match.

### Title ###

The title for the documentation is parsed from the root `README.md` file by taking text from the first line,
which is indicated by markdown as a title. Then the word `API` is appended to it.

For example, if documentation was generated from this repository, the title would be
`Riimu's Automatic Sami Configuration API`.

If no title can be determined, an exception will be thrown and the process is interrupted.

### Versions ###

The configuration utility automatically looks up the latest semver stable tag from the repository and checkouts that
for the purpose of generating the documentation. Not that if you create the tags as releases in Github, remember
to run `git fetch` in order to also get the tags locally.

After the documentation is generated, the previous selected working state is checked out again.

If no applicable tags can be found or the workspace is not clean for checking out another tag, an exception will be
thrown and the process interrupted.

### Build directory ###

The build directory will be set to `build/doc` and the cache path to `build/cache` in the project root directory.
Do note that both of these directories will be cleared entirely before the documentation generation process.

### Remote Repository URL ###

The repository url will be set as a github repository url based on what is set as the url for `origin` remote in the
local git repository.

If no valid github url is set as the remote url for `origin`, an exception will be thrown and the process interrupted.

## Credits ##

This package is Copyright (c) 2018 Riikka Kalliomäki.

See LICENSE for license and copying information.

[Sami]: https://github.com/FriendsOfPHP/Sami
