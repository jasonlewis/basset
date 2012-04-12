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
 * Routes for Basset are very simple, the method is either 'styles' or 
 * 'scripts' depending on what you want to use, the first parameter is the
 * route that will be handled. For example:
 * 
 * Basset::styles('website', function($basset) {});
 * 
 * The above will generate a route for yoursite.com/basset/website.css
 * Note the extension is placed automatically for you. You can change
 * the 'basset' by configuring the 'handles' option in your
 * application/bundles.php file.
 * 
 * Please refer to the README.md for further help.
 */
Basset::styles('example', function($basset)
{
	$basset->add('website', 'website.css');
});

Basset::scripts('example', function($basset)
{
	$basset->add('jquery', 'jquery.js');
});