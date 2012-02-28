<?php

Route::get('(:bundle)/example.css', function()
{
	return Basset\Basset::add('normalize', 'normalize.css')
		->add('website', 'website.css');
});
