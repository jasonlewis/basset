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
Basset::styles('example', function($basset)
{

		// Add a normal asset located at public/css/main.css
$basset->add('main', 'main.css')
		// Create a new collection. Collections are groups of assets contained within the same directory. Directories are
		// relative from the root of your application, NOT the public directory.
	   ->collection('assets/less', function($basset)
	    {
			$basset->add('less-styles', 'less-styles.less', 'links');
		})
		// Add a normal asset this time specifying it's containing folder within the public directory. It can be anything
		// we want.
	   ->add('links', 'css/links.css')
		// Create a new collection within a bundle. This is a shortcut for defining a collection at: public/bundles/example/assets/css
	   ->collection('example::assets/css', function($basset)
	    {
	   		$basset->add('example-style', 'style.css');
	    })
		// Add a normal bundle asset not within a collection. This will link to the CSS directory within public/bundles/example/css/css.css
		// We could however specify our own path with example::our/own/path/css.css
	   ->add('bundle-normal', 'example::css.css');
});

Basset::scripts('example', function($basset)
{
	$basset->add('jquery', 'jquery.js');
});