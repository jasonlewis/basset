<?php

Autoloader::map(array(
	'Basset\\Basset'			=> Bundle::path('basset') . 'basset.php',
	'Basset\\Libs\\CSSCompress'	=> Bundle::path('basset') . 'libraries/csscompress.php',
	'Basset\\Libs\\JSMin'		=> Bundle::path('basset') . 'libraries/jsmin.php',
	'Basset\\Libs\\URIRewriter'	=> Bundle::path('basset') . 'libraries/urirewriter.php'
));

/**
 * Do not alter this before filter, as it ensures that the correct properties are set to the
 * routed assets.
 */
Route::filter('basset::before', function()
{
	Basset\Basset::routed();
});

/**
 * If you append a file extension such as .css or .js to the end of your routes then it will
 * automatically set the content type instead of manually specifying an after filter.
 *
 * @param  object  $response
 */
Route::filter('basset::after', function($response)
{
	$types = array(
		'css' 	=> 'text/css',
		'js'	=> 'text/javascript'
	);

	$extension = File::extension($uri = Request::uri());

	if(array_key_exists($extension, $types))
	{
		$response->header('Content-Type', $types[$extension]);
	}
});

/**
 * This is the manual CSS filter to set the content type for the response to text/css.
 *
 * @param  object  $response
 */
Route::filter('basset::css', function($response)
{
	$response->header('Content-Type', 'text/css');
});

/**
 * This is the manual JS filter to set the content type for the response to text/javascript.
 *
 * @param  object  $response
 */
Route::filter('basset::js', function($response)
{
	$response->header('Content-Type', 'text/javascript');
});