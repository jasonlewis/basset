<?php

return array(

    /*
    |--------------------------------------------------------------------------
    | Production Environment
    |--------------------------------------------------------------------------
    |
    | Basset needs to know what your production environment is so that it can
    | respond with the correct assets. When in production Basset will attempt
    | to return any built collections. If a collection has not been built
    | Basset will look for development assets that may have been built. If
    | neither can be found Basset will dynamically route to each asset in the
    | collection and apply the filters.
    |
    | The last method can be very taxing so it's highly recommended that
    | collections are built when deploying to a production environment.
    |
    */

    'production' => 'production',

    /*
    |--------------------------------------------------------------------------
    | Build Remote Assets
    |--------------------------------------------------------------------------
    |
    | Remote assets are often stored on a CDN. This means you normally wouldn't
    | want to build the remote assets as part of your collection. Disabling
    | this will result in all remote assets to still be included but as
    | separate HTML tags.
    |
    */

    'build_remotes' => true,

    /*
    |--------------------------------------------------------------------------
    | Build Path
    |--------------------------------------------------------------------------
    |
    | When assets are built with Artisan they will be stored within a directory
    | relative to the public directory.
    |
    | If the directory does not exist Basset will attempt to create it.
    |
    */

    'build_path' => 'assets',

    /*
    |--------------------------------------------------------------------------
    | Named Directories
    |--------------------------------------------------------------------------
    |
    | These directories are a convenience when requiring a directory or tree.
    | Basset will also revert to recursively scanning these directories if it
    | cannot locate an asset.
    |
    | Directory paths should be relative to the public directory.
    |
    | You can specifiy an absolute path to a directory by prefixing it with
    | 'path: '.
    |
    | array(
    |    'js' => 'javascripts',
    |    'css' => 'path: /absolute/path/to/your/css'
    | )
    |
    */

    'directories' => array(
        'css' => 'css'
    ),

    /*
    |--------------------------------------------------------------------------
    | Asset Aliases
    |--------------------------------------------------------------------------
    |
    | Adding the same asset to a number of collections can be a pain when you
    | have to define its path every time. With aliases you can give your assets
    | a name and simply use that name when adding an asset.
    |
    | array(
    |    'layout' => 'css/layout.css'
    | )
    |
    */

    'assets' => array(),

    /*
    |--------------------------------------------------------------------------
    | Named Filters
    |--------------------------------------------------------------------------
    |
    | A named filter can be used to quickly apply a filter to a collection of
    | assets or an individual asset.
    |
    |   'YuiCss' => 'Yui\CssCompressorFilter'
    |
    | If you'd like to specify options for a named filter you can define the
    | filter as an array, the value should be a closure where you can set
    | arguments for the constructor, etc.
    |
    |   'YuiCss' => array(
    |       'Yui\CssCompressorFilter' => function($filter)
    |       {
    |           $filter->setArguments('path/to/jar');
    |       }
    |   )
    |
    | The filter can then be referenced by its name when applying.
    |
    */

    'filters' => array()

);