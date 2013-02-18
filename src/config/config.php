<?php

return array(

	/*
	|--------------------------------------------------------------------------
	| Compiling Path
	|--------------------------------------------------------------------------
	|
	| When assets are statically compiled via the command line the generated
	| files will be stored in this directory. The path is relative to the public
	| directory.
	|
	| If the directory does not exist, Basset will attempt to create it.
	|
	*/

	'compiling_path' => 'assets',

	/*
	|--------------------------------------------------------------------------
	| Named Asset Directories
	|--------------------------------------------------------------------------
	|
	| These named directories are used for quick reference as well as when
	| searching for an asset. Assets are located by cascading through the array
	| of directories until an asset with the matching name is found.
	|
	| Directories are relative from the public directory.
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
		'css' => 'css'
	),

	/*
	|--------------------------------------------------------------------------
	| Asset Aliases
	|--------------------------------------------------------------------------
	|
	| Similar to directories you can define names for assets that may be used
	| in a number of collections.
	|
	| array(
	| 	 'layout' => 'css/layout.css'
	| )
	|
	| Aliased assets are checked first when adding an asset.
	|
	*/

	'assets' => array(),

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