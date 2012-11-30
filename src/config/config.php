<?php

return array(

	/*
	|--------------------------------------------------------------------------
	| Basset Handles
	|--------------------------------------------------------------------------
	|
	| When requesting assets in development mode this is the key that routes
	| will respond to.
	|
	*/

	'handles' => 'assets',

	/*
	|--------------------------------------------------------------------------
	| Asset Directories
	|--------------------------------------------------------------------------
	|
	| These named directories are used for quick reference as well as when
	| searching for an asset. Assets are located by cascading through the array
	| of directories until an asset with the matching name is found.
	|
	| Directories are relative from the root of your application.
	|
	| You can specifiy an absolute path to a directory by prefixing it with
	| 'path: '.
	|
	| array(
	| 	 'css' => 'path: /path/to/your/directory'
	| )
	|
	*/

	'directories' => array(
		'css' => 'app/assets/css',
		'js' => 'app/assets/js'
	),

	/*
	|--------------------------------------------------------------------------
	| Asset Collections
	|--------------------------------------------------------------------------
	|
	| Define your collections in an array like so.
	|
	| array(
	| 	'website' => function($collection)
	|	{
	|		$collection->add('example.css');
	|	}
	| )
	|
	| This collection is now available at Basset::show('website.css')
	|
	*/
	'collections' => array(),

	/*
	|--------------------------------------------------------------------------
	| Compiling Path
	|--------------------------------------------------------------------------
	|
	| When assets are statically compiled via the command line the generated
	| files will be stored in this directory. The path is relative to the root
	| of your application, so remember to compile them to the public directory
	| somewhere.
	|
	| If the directory does not exist, Basset will attempt to create it.
	|
	*/

	'compiling_path' => 'public/assets',

	/*
	|--------------------------------------------------------------------------
	| Production Environment
	|--------------------------------------------------------------------------
	|
	| Basset will attempt to detect your production environment and serve
	| static assets. You can help Basset out in a number of ways to speed it up
	| a bit.
	|
	| Set your actual production environment here and Basset will compare
	| environments and serve the appropriate assets.
	|
	| Set to null or an empty string and Basset will try and detect your
	| environment, this may deliver unexpected results.
	|
	| Set to false and Basset will always serve individual assets as it does in
	| a development environment. Remember that filters will not be applied
	| to the assets.
	|
	| Set to true to always serve static assets if available.
	|
	*/

	'production_environment' => '',

	/*
	|--------------------------------------------------------------------------
	| Named Filters
	|--------------------------------------------------------------------------
	|
	| A named filter can be used to quickly apply a filter to a collection of
	| assets.
	| 
	|	'YuiCss' => 'Yui\CssCompressorFilter'
	|
	| If you'd like to specify options for a named filter you can define the
	| filter as an array.
	|
	|	'YuiCss' => array(
	|		'Yui\CssCompressorFilter' => array('/path/to/yuicompressor.jar')
	|	)
	|
	| The filter can then be referenced by its name when applying filters.
	|
	*/

	'filters' => array()

);