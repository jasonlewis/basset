<?php

Route::get('(:bundle)/website.css', function()
{
	return Basset\Basset::add('normalize', 'normalize.css')
			->add('website', 'lcf.css')
			->add('forms', 'forms.css')
			->add('tooltip', 'tooltip.css');
});

Route::get('(:bundle)/website.js', function()
{
	return Basset\Basset::add('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js')
			->add('underscore', 'underscore.js')
			->add('json', 'json.js')
			->add('tooltip', 'tooltip.js')
			->add('follow', 'follow.js')
			->add('placeholder', 'placeholder.js')
			->add('website', 'lcf.js');
});