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

	'compiling' => array(
		/**
		 * Compiling
		 * 
		 * Similar to caching apart from that the assets will only be recompiled when a change is
		 * detected within one of the assets. This is not recommended for production apps. It is
		 * best to use caching once an application goes live.
		 */
		'enabled' => false
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
	)
);