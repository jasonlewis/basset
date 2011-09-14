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
	 * the path name to the filename, example:
	 *
	 * Basset::add('website::template.css')
	 *
	 * This would add in http://yourwebsite/public/assets/website/css/template.css
	 * 
	 * Examples depend on how you have your environment setup.
	 */
	'paths' 	=> array(
		'default' 		=> ''
	),

	/**
	 * The names of the CSS, LESS and JS folders.
	 */
	'folders' 	=> array(
		'css'	=> 'css',
		'less'	=> 'less',
		'js'	=> 'js',
	),

	/**
	 * Whether or not to compress all files.
	 * You can set this to an array and compress one type and not the other. Example:
	 *
	 * array(
	 * 	'style' 	=> false,
	 * 	'script'	=> true
	 * )
	 *
	 * This would compress JavaScript files but not stylesheet files.
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
	 * This is the time in HOURS you wish to cache items for. The default value
	 * of 744 is one month (31 days). You can increase this to whichever number
	 * you want.
	 */
	'cache_for'	=> 744,

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
	'preserve_lines' => false,

	/**
	 * LESS related settings. Basset will detect your LESS stylesheets by
	 * using the extension .less on those which are LESS styles. LESS is used on a per-file
	 * basis meaning you can use it in conjunction with regular stylesheets.
	 */
	'less' => array(

		/**
		 * If you do not have the LESS compiler or don't want to use the client-side copy then
	 	 * you can chose to enable the LessPHP compiler.
		 *
		 * See http://leafo.net/lessphp/ for more details on the compiler as there are slight
		 * differences and known problems.
		 */
		'php_compiler' => false
	)
);