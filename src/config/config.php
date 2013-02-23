<?php

return array(

	/*
	|--------------------------------------------------------------------------
	| Compile Remotes
	|--------------------------------------------------------------------------
	|
	| When you add a remote asset it's often located on a CDN and you don't
	| want it compiled into your collection. Disabling the compiling of all
	| remote assets means they won't be included in the compiled collection.
	|
	*/

	'compile_remotes' => true,

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
	| filter as an array, the value should be a closure where you can set
	| arguments for the constructor, etc.
	|
	|	'YuiCss' => array(
	|		'Yui\CssCompressorFilter' => function($filter)
	|		{
	|			$filter->setArguments('path/to/jar');
	|		}
	|	)
	|
	| The filter can then be referenced by its name when applying filters.
	|
	*/

	'filters' => array()

);