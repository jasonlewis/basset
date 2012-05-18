<?php

Autoloader::map(array(
	'Basset'			  		  => __DIR__ . DS . 'classes' . DS . 'basset.php',
	'Basset\\Asset'				  => __DIR__ . DS . 'classes' . DS . 'asset.php',
	'Basset\\Cache'				  => __DIR__ . DS . 'classes' . DS . 'cache.php',
	'Basset\\Config'			  => __DIR__ . DS . 'classes' . DS . 'config.php',
	'Basset\\Container'			  => __DIR__ . DS . 'classes' . DS . 'container.php',
	'Basset\\Vendor\\CSSCompress' => __DIR__ . DS . 'classes' . DS . 'vendor' . DS . 'csscompress.php',
	'Basset\\Vendor\\JSMin'		  => __DIR__ . DS . 'classes' . DS . 'vendor' . DS . 'jsmin.php',
	'Basset\\Vendor\\URIRewriter' => __DIR__ . DS . 'classes' . DS . 'vendor' . DS . 'urirewriter.php',
	'Basset\\Vendor\\lessc'		  => __DIR__ . DS . 'classes' . DS . 'vendor' . DS . 'less.php'
));

Route::filter('basset::after', function($response)
{
	$types = array(
		'less'  => 'text/css',
		'sass'  => 'text/css',
		'scss'  => 'text/css',
		'css' 	=> 'text/css',
		'js'	=> 'text/javascript'
	);

	$extension = File::extension($uri = Request::uri());

	if(array_key_exists($extension, $types))
	{
		$response->header('Content-Type', $types[$extension]);
	}

	// To prevent any further output being added to any Basset routes we'll clear any events listening
	// for the laravel.done event.
	Event::clear('laravel.done');
});