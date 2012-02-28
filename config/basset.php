<?php

return array(
	/**
	 * Compress assets to achieve smaller file size. This should not be enabled until
	 * application is deployed and caching is enabled as well.
	 */
	'compress' 	=> false,

	/**
	 * When deploying an application, ensure you set this to true to achieve best
	 * loading times. This uses the Laravel caching mechanism.
	 */
	'caching'	=> false,

	/**
	 * This is the time in MINUTES you wish to cache items for. The default value
	 * of 44,640 is one month (31 days). You can change this to whatever number
	 * you want.
	 */
	'cache_for'	=> 44640,

	/**
	 * Whether or not to preserve some new line characters. By default all new lines
	 * are stripped but if you want you can strip out all new lines to attain a single
	 * line of compressed code. The amount of size saved from stripping out all new lines
	 * is less than 1% in an average sized 5-10kb file. The only benefit gained from not
	 * preserving lines is a nice looking single-line file.
	 *
	 * Note: This only applies to CSS compression.
	 */
	'preserve_lines' => false
);