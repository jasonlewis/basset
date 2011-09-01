<?php
return array(
	/**
	 * An array of paths relative to the public directory.
	 * If for example you have some assets in public/assets/website/ then a valid path
	 * would be:
	 *
	 * array('website' => 'assets/website')
	 *
	 * Where 'website' is the name of the path. Files can then be added by prefixing
	 * the path name or 'namespace' to to filename, example:
	 *
	 * Basset::make()->add('website::template.css')
	 *
	 * This would add in http://yourwebsite/public/assets/website/css/template.css
	 * Examples depend on how you have your environment setup.
	 */
	'paths' 	=> array(
		'default' 	=> '',
	),

	/**
	 * The name of the css and js subfolders.
	 */
	'folders' 	=> array(
		'css' 	=> 'css',
		'js'	=> 'js',
	),

	/**
	 * Whether or not to compress all files.
	 * You can set this to an array and compress one type and not the other. Example:
	 *
	 * array(
	 * 	'css' 	=> false,
	 * 	'js'	=> true
	 * )
	 *
	 * This would compress JavaScript files but not CSS files.
	 *
	 * Note: Compression will combine ALL files into a single file regardless of
	 * the setting below.
	 */
	'compress' 	=> false,

	/**
	 * Whether or not to combine the assets into a single file.
	 * Combining of assets is only available when using route based loading or inline
	 * loading of assets. Standard attribute loading cannot combine files at this stage.
	 */
	'combine' 	=> false,

	/**
	 * Whether or not to cache items. This is best done when deploying your website.
	 * For more details see the readme or visit the docs.
	 */
	'caching'	=> false,

	/**
	 * This is the time in MINUTES you wish to cache items for. The default value
	 * of 44,640 is one month (31 days). You can increase this to whichever number
	 * you want.
	 */
	'cache_for'	=> 44640,

	/**
	 * Whether or not to preserve some new line characters. By default all
	 * new lines are stripped but if you want you can strip out all new lines
	 * to attain a single line of compressed code.
	 * The amount of size saved from stripping out all new lines is less than
	 * 1% in an average sized 5-10kb file. The only benefit gained from not
	 * preserving lines is a nice looking single-line file.
	 *
	 * Note: This only applies to CSS compression.
	 */
	'preserve_lines' => false
);