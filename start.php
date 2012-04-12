<?php

Autoloader::map(array(
	'Basset'			  => __DIR__ . DS . 'basset.php',
	'Basset\\CSSCompress' => __DIR__ . DS . 'vendor/csscompress.php',
	'Basset\\JSMin'		  => __DIR__ . DS . 'vendor/jsmin.php',
	'Basset\\URIRewriter' => __DIR__ . DS . 'vendor/urirewriter.php',
	'Basset\\lessc'		  => __DIR__ . DS . 'vendor/less.php'
));

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