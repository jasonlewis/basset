<?php

Autoloader::map(array(
	'Basset'			  		  => __DIR__ . DS . 'basset.php',
	'Basset\\Vendor\\CSSCompress' => __DIR__ . DS . 'classes' . DS . 'vendor' . DS . 'csscompress.php',
	'Basset\\Vendor\\JSMin'		  => __DIR__ . DS . 'classes' . DS . 'vendor' . DS . 'jsmin.php',
	'Basset\\Vendor\\URIRewriter' => __DIR__ . DS . 'classes' . DS . 'vendor' . DS . 'urirewriter.php',
	'Basset\\Vendor\\lessc'		  => __DIR__ . DS . 'classes' . DS . 'vendor' . DS . 'less.php'
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