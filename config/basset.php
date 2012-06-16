<?php

return array(
	'compression' => array(
		/**
		 * Compression
		 *
		 * Globally enable compression for all assets, this is only recommended once an application
		 * is live and when used in conjunction with caching.
		 */
		'enabled' => false,

		/**
		 * Preserve Lines
		 *
		 * When compressing CSS files you may experience problems with extremely large files. You can
		 * enable preserving of lines to maintain the occasional line break to split the file up
		 * instead of one long continuous line.
		 */
		'preserve_lines' => false
	),

	'caching' => array(
		/**
		 * Caching
		 *
		 * Globally enable caching for all assets, this is only recommended once an application
		 * is live.
		 */
		'enabled' => false,

		/**
		 * Time
		 *
		 * The time in minutes to cache the assets for. By default it is set to one month, or 44640
		 * minutes.
		 */
		'time' => 44640,
	),

	'less' => array(
		/**
		 * LessPHP Compiler
		 *
		 * Use the LessPHP compiler to compile .less files, handy if you do not have LESS installed
		 * on your server and you still want the LESS functionality.
		 */
		'php' => false
	),

	'compiling' => array(
		/**
		 * Compiled Directory
		 * 
		 * The directory to save the compiled Basset files, ensure this directory is writeable.
		 */
		'directory' => Bundle::path('basset') . 'compiled',

		/**
		 * Recompile
		 * 
		 * Sometimes you may wish to have assets recompiled every time, setting this option to true will
		 * allow this. Don't forget to set it to false on a live website for maximum performance.
		 */
		'recompile' => false
	),

	/**
	 * Document Root
	 *
	 * The document root of the website in which the CSS files reside. If no document root is provided
	 * then Basset uses $_SERVER['DOCUMENT_ROOT']
	 */
	'document_root' => '',

	/**
	 * Symlinks
	 *
	 * An array of user specified symlinks. If the CSS files are stored in symlink'd directories provide
	 * an array of link paths to target paths, where the link paths are within the document root. Because
	 * paths need to be normalized for this to work you may use "//" to substitute the document root
	 * in the link paths.
	 *
	 * Example:
	 *
	 * array('//symlink' => '/path/to/target')
	 */
	'symlinks' => array()
);