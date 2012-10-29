<?php

/*
|--------------------------------------------------------------------------
| Basset Routes
|--------------------------------------------------------------------------
|
| Registering routes with Basset is a piece of cake. Simply tell Basset
| what you would like to use (scripts or styles) and begin registering your
| assets.
|
| Let's register styles that respond to http://localhost/basset/website.css
|
| 		Basset::styles('website', function($basset)
|		{
|			$basset->add('theme', 'theme.css');
|		});
|
| The extension and the bundles handler is added automatically for you, all
| you need to do is supply a name.
|
| If you'd like to register scripts, simply swap 'styles' with 'scripts'
| and begin adding your assets.
|
*/

/*
|--------------------------------------------------------------------------
| Keep This Bad Boy
|--------------------------------------------------------------------------
|
| Please leave this route alone, Basset needs it!
|
*/
Route::get('(:bundle)/(:all)', function()
{
	$headers = array(
		'Cache-Control'	=> 'max-age='.(24*60*60).', public',
		'Pragma'	=> 'cache',
		'Last-Modified'	=> gmdate('D, d M Y H:i:s').' GMT'
	);
	
	return Response::make(Basset::compiled(), 200, $headers);
});