# Basset

Basset is a Better Asset manager for the Laravel PHP framework. Basset allows you to generate asset routes which can be compressed and cached to maximize website performance. Basset also allows compressed and cached assets to appear inline.

- **Author:** Jason Lewis
- **Website:** [http://jasonlewis.me/code/basset](http://jasonlewis.me/code/basset)
- **Version:** 1.4.2

## Documentation
For a complete documentation guide please see the [official documentation](http://jasonlewis.me/code/basset/docs).

## Copyright and License
Basset was written by Jason Lewis for the Laravel framework.
Basset is released under the MIT License. See the LICENSE file for details.

Copyright 2011-2012 Jason Lewis

## Changelog

### Basset 1.4.3
- Compiled directory can now be set in the configuration.
- Numerous bug fixes.

### Basset 1.4.2
- Fixed issue #10, set method on config was not static.
- Fixed issue #9, missed an old settings reference. Config has been made statically global.

### Basset 1.4.1
- Fixed issue #8, symlinks and files weren't working correctly.

### Basset 1.4
- Updated lessphp to 0.3.4-2.
- Refactored a lot of the code, things are neater and in their own place.
- Extendable configuration, prevents overwriting of configuration files on upgrade.
- Fix for removing profiler and any output being displayed for Basset routes.
- Fixed #6, added symlink functionality.
- Added in a development mode.
- Basset now only recompiles when changes detected by default.

### Basset 1.3.2
- Bug fix with CSS URIs not being rewritten correctly causing badly formed links to images.

### Basset 1.3.1
- Bug fix with compression, compiling, and inline assets not working.

### Basset 1.3
- Updated API, routes are now defined with Basset::styles() and Basset::scripts()
- LESS support has been reintegrated.
- Directory support.
- Compiling support.

### Basset 1.2
- Now ships as a bundle for Laravel 3.
- Better route based loading support.
- Removal of LESS support for the time being.

### Basset 1.1
- Added LESS compatibility.
- Bug fix in asset dependency sorting.
- Dependency is now not type-limited.
- General code cleanup.

### Bassset 1.0
- Initial release of Basset.