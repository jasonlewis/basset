<?php

/**
 * 
 * Example Basset Route
 * 
 * -----------------------------------------------------------------------
 * 
 * The following are example routes, one CSS and one JS, that you can use
 * as a model for your own routes.
 * 
 * Routes for Basset are very simple, the method is either 'css' or 'js'
 * depending on what you want to use, the first parameter is the route
 * that will be handled. For example:
 * 
 * Basset::css('website', function($basset) {});
 * 
 * The above will generate a route for yoursite.com/basset/website.css
 * Note the extension is placed automatically for you.
 * 
 * Please refer to the README.md for further help.
 */
Basset::css('example', function($basset)
{
	$basset->add('normalize', 'normalize.css')
		   ->add('website', 'website.css');
});

Basset::js('example', function($basset)
{
	$basset->add('jquery', 'jquery.js');
});