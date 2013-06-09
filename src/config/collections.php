<?php

return array(

	/*
    |--------------------------------------------------------------------------
    | Collections
    |--------------------------------------------------------------------------
    |
    | Basset is built around collections. A collection contains assets for
    | your application. Collections can contain both stylesheets and
    | javascripts.
    |
    | A default "application" collection is ready for immediate use. It makes
    | a couple of assumptions about your directory structure.
    |
    | /public
    |    /assets
    |        /stylesheets
    |            /less
    |            /sass
    |        /javascripts
    |            /coffeescripts
    |
    | You can overwrite this collection or remove it by publishing the config.
    |
    */
    
    'application' => function($collection)
    {
        // Switch to the stylesheets directory and require the "less" and "sass" directories.
        // These directories both have a filter applied to them so that the built
        // collection will contain valid CSS.
        $directory = $collection->directory('assets/stylesheets', function($collection)
        {
            $collection->requireDirectory('less')->apply('Less');
            $collection->requireDirectory('sass')->apply('Sass');
            $collection->requireDirectory();
        });

        $directory->apply('CssMin');
        $directory->apply('UriRewriteFilter');

        // Switch to the javascripts directory and require the "coffeescript" directory. As
        // with the above directories we'll apply the CoffeeScript filter to the directory
        // so the built collection contains valid JS.
        $directory = $collection->directory('assets/javascripts', function($collection)
        {
            $collection->requireDirectory('coffeescripts')->apply('CoffeeScript');
            $collection->requireDirectory();
        });

        $directory->apply('JsMin');
    }

);