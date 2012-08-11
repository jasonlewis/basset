<?php

/**
 * Register the core libraries and vendor libraries that Basset uses with the autoloader.
 */
Autoloader::map(array(
	'Basset'			  		  => __DIR__ . DS . 'libraries' . DS . 'core' . DS . 'basset.php',
	'Basset\\Asset'				  => __DIR__ . DS . 'libraries' . DS . 'core' . DS . 'asset.php',
	'Basset\\Cache'				  => __DIR__ . DS . 'libraries' . DS . 'core' . DS . 'cache.php',
	'Basset\\Config'			  => __DIR__ . DS . 'libraries' . DS . 'core' . DS . 'config.php',
	'Basset\\Container'			  => __DIR__ . DS . 'libraries' . DS . 'core' . DS . 'container.php',
	'Basset\\Vendor\\CSSCompress' => __DIR__ . DS . 'libraries' . DS . 'vendor' . DS . 'csscompress.php',
	'Basset\\Vendor\\JSMin'		  => __DIR__ . DS . 'libraries' . DS . 'vendor' . DS . 'jsmin.php',
	'Basset\\Vendor\\URIRewriter' => __DIR__ . DS . 'libraries' . DS . 'vendor' . DS . 'urirewriter.php',
	'Basset\\Vendor\\lessc'		  => __DIR__ . DS . 'libraries' . DS . 'vendor' . DS . 'less.php'
));

if(starts_with(URI::current(), Bundle::option('basset', 'handles')))
{
	/**
	 * In this before filter we'll grab the compiled assets for this route and return them here.
	 * This is what makes it possible for Basset routes to be adjusted prior to them being displayed.
	 */
	$handler = Bundle::handles(URI::current());

	Route::filter("{$handler}::before", function()
	{
		Config::set('session.driver', '');
		
		return Basset::compiled();
	});

	/**
	 * After the Basset route is run we'll adjust the response object setting the appropriate content
	 * type for the assets.
	 */
	Route::filter("{$handler}::after", function($response)
	{
		$types = array(
			'less'  => 'text/css',
			'sass'  => 'text/css',
			'scss'  => 'text/css',
			'css' 	=> 'text/css',
			'js'	=> 'text/javascript'
		);

		$extension = File::extension(Request::uri());

		if(array_key_exists($extension, $types))
		{
			$response->header('Content-Type', $types[$extension]);
		}

		// If the browser accepts gzip encoding we'll encode the content and send the
		// appropriate headers to compress the output.
		if(str_contains($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip'))
		{
			$response->content = gzencode($response->content);

			$response->header('Content-Encoding', 'gzip');

			$response->header('Vary', 'Accept-Encoding');

			$response->header('Content-Length', Str::length($response->content));

			// Attempt to disable the zlib output compression so that we can use gzip content encoding.
			if(ini_get('zlib.output_compression'))
			{
				ini_set('zlib.output_compression', 0);
			}
		}

		// To prevent any further output being added to any Basset routes we'll clear any events listening
		// for the laravel.done event.
		Event::clear('laravel.done');
	});
}

/**
 * If the current URI is not being handled by Basset then all registered Basset routes will be
 * compiled once Laravel has finished doing its thing.
 */
else
{
	Event::listen('laravel.done', function()
	{
		Basset::compile();
	});
}