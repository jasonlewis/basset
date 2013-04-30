## Basset 4 (pre-beta)

[![Build Status](https://secure.travis-ci.org/jasonlewis/basset.png)](http://travis-ci.org/jasonlewis/basset)

G'day folks! Just a quick heads up before you go any further. I'm classifying this as "pre-beta" right now as not everything has been implemented. That said I'd like for people to give it a whirl and let me know any immediate problems they encounter (you most likely will encounter some!). I've been testing it but am yet to test everything together.

Just to re-iterate: this is by no means finished code and bugs ARE to be expected.

### Known Issues

- Unknown issue with `Assetic\Filter\StylusFilter` on Windows environment.
- Unknown issue with `Assetic\Filter\LessFilter` (not the `LessphpFilter`) on Windows environment.

### Changes

- Collections are rendered with `basset_javascript()` and `basset_stylesheet()`.
- Simplified asset finding process.
- Can no longer prefix paths with `path:` for an absolute path, use relative paths from public directory instead.
- Filters are now applied with a fluent syntax.
- Filters can find any missing constructor arguments such as the path to Node, etc.
- Default `application` collection is bundled.
- `basset:compile` command is now `basset:build`.
- Old collection builds are cleaned automatically but can be cleaned manually with `basset:clean`.
- Packages can be registered with Basset::package() and assets can be added using the namespace syntax found through Laravel.
- `CssoFilter` support.
- Fixed issues with `UriRewriteFilter`.

### Still Awaiting Implementation

- Build assets with a `--gzip` flag for maximum compression.
- Deploy built collections to a CDN.

### Installation

Get the package and register the `Basset\BassetServiceProvider` provider as well as the `Basset\Facade` alias.

### Usage Example

The following example demonstrates some of the features available. You can also check the default `application` collection in `src/config/config.php`.

```php
Basset::collection('example', function($collection)
{
    // Change our working directory to "css". This directory is located within the
    // public directory. You don't have to perform a variable assignment here,
    // this is just done for commenting convenience when applying the filter
    // in the code below.
    $directory = $collection->directory('css', function($collection)
    {
        // Recursively iterate through the entire "css/less" directory adding all
        // the files.
        $collection->requireTree('less');

        // Require all the files within the "css" directory as well.
        $collection->requireDirectory();
    });

    // Apply the "LessFilter" to all of the added assets that have a ".less"
    // extension. We can also tell the filter to find any missing constructor
    // arguments and populate them.
    $directory->apply('LessFilter')->to('*.less')->findMissingConstructorArgs();

    // Add jQuery from a remote CDN. By default this asset will be ignored when
    // we try to build. If we wanted to we could ->include() it and it will
    // be built with the collection.
    $collection->add('http://code.jquery.com/jquery-1.9.1.min.js');

    $collection->directory('js', function($collection)
    {
        $directory = $collection->requireDirectory('coffee');

        // If you don't want to leave it up to Basset to find your missing
        // constructor arguments you can specify them manually if you have
        // odd paths.
        $directory->apply('CoffeeScriptFilter')
                  ->setArguments('path/to/coffee', 'path/to/node')
                  ->beforeFiltering(function($filter)
                  {
                        // You can bind closures to a before filtering event
                        // that's fired after the instantiation of the
                        // filter. This allows you to tinker with the raw
                        // filter instance. In this example we are telling
                        // the filter to compile without a top-level
                        // function wrapper.
                        $filter->setBare(true);
                  });
    });
})->apply('UriRewriteFilter')->onlyStylesheets();
```