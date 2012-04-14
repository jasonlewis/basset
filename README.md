# Basset

Basset is a Better Asset manager for the Laravel PHP framework. Basset allows you to generate asset routes which can be compressed and cached to maximize website performance. Basset also allows compressed and cached assets to appear inline.

- **Author:** Jason Lewis
- **Website:** [http://jasonlewis.me/code/basset](http://jasonlewis.me/code/basset)
- **Version:** 1.3.2

## Installation

1. Install via Artisan CLI: `php artisan bundle:install basset`
2. Register the Basset bundle in **application/bundles.php**

    ~~~~
    'basset' => array(
	    'handles' => 'basset',
	    'auto'	  => true
    )
    ~~~~

3. Open the **bundles/basset/config/basset.php** config file and make changes where you see fit.
4. Begin using Basset!

## Upgrading

**Before upgrading!** Remember to backup your `bundles/basset/routes.php` file as it may be overwritten during upgrade.

You can upgrade via the Artisan CLI: `php artisan bundle:upgrade basset`

## Congfiguration
The configuration file is commented quite well. I'd just like to point out a few things here.

1. You should not be using both compiling and caching. Pick one. Caching is best for a live application, compiling is great in a development environment.
2. The Less PHP compiler is disabled by default, remember to turn it on if you want your Less stylesheets compiled.
3. Compression is best used once an application goes live.

## Basics
Much of the Laravel Asset class functionality is retained in Basset since Basset is based partly off this class. There is little to configure out of the box unless you want to
enable global compression and caching for all assets.

Inline assets are pretty important, let's take a look at a composer which might add a couple of assets.

~~~~
View::compose('layout', function($view)
{
	Basset::inline('assets')
		->add('bootstrap', 'js/bootstrap.js')
		->add('ie6', 'css/ie6.css');
});
~~~~

The `Basset::inline()` method accepts a single parameter which will be the name of the container (similar to containers from the Asset class). Now in our layout view we can render the assets. We might want the inline CSS in our head and the inline JS at the bottom of our body.

~~~~
...

	<?php echo Basset::inline('assets')->styles(); ?>

</head>

<body>

	...

	<?php echo Basset::inline('assets')->scripts(); ?>
</body>
~~~~

It's as easy as that. We could have multiple composers that add assets to the same container if we needed to!

If you want to just link to your assets then please use the Asset class, Basset is designed with compression.

### Dependencies
An optional third parameter is available for dependency sorting. You can give the name of an asset that it will depend on or an array of names.

~~~~
Basset::inline('assets')->add('template', 'css/template.css', 'things')->add('things', 'css/things.css');
~~~~

Now **template** depends on **things**, so it will be loaded after **things** has been loaded.
If no dependency is given then assets are loaded based on the order they are listed.

### External Assets
In some cases you may want to link to external assets, such as jQuery hosted by Google. Don't stress, simply write the full URL and Basset will
handle it for you.

## Route Based Loading
So far I've been showing you how to use inline assets, but the best part about Basset is the route based loading.

Let's take a look at some routes. Open up **bundles/basset/routes.php** and look at the example CSS route.

~~~~
Basset::styles('example', function($basset)
{
	$basset->add('website', 'website.css');
});
~~~~

It's that easy. We don't worry about the full route or including the extension, just the name of the route and the Basset callback. You may have noticed the asset is just `website.css`, Basset is smart enough to detect the file extension and will assume your file is at `public/css/website.css`

You can specify your own path though:

~~~~
$basset->add('website', 'your/own/path/website.css');
~~~~

Just remember that this path is relative from the `public` directory.

Displaying the assets is also just as easy. In the head of your document just use Laravel's HTML class to load your Basset route:

~~~~
echo HTML::style('/basset/example.css');
~~~~

Remember, JavaScript files work the same. Just replace **styles** with **scripts** when defining the route.

## Compression
You can compress both inline and route based assets. To globally enable compression just set the option in the configuration file. To set the option on a per asset basis you can do it like so:

~~~~
Basset::inline('assets')->add('template', 'css/template.css')->compress();
~~~~

Or for your routes:

~~~~
Basset::styles('example', function($basset)
{
	$basset->add('website', 'website.css')
		   ->compress();
});
~~~~

**Read before you compress your assets!**

When developing a local application there is no need to be compressing assets. When your application becomes live that's when compression should be used and only in conjunction with caching. You may like to enable this along with **compiling**.

## Compiling
When developing an application it can be handy to enable compiling in conjunction with compression. Before rendering your assets Basset will determine whether or not the assets need to be recompiled again by checking when your files were last modified. If you recently made a change since the assets were compiled last, Basset will recompile your assets for you. Compiling can be enabled in the configuration file or on a per asset basis.

~~~~
Basset::inline('assets')->add('template', 'template.css')->compile();
~~~~

Or for your routes:

~~~~
Basset::styles('example', function($basset)
{
	$basset->add('website', 'website.css')
		   ->compile();
});
~~~~

### Clearing compiled assets
If you want to delete a compiled asset file you can use the forget method.

~~~~
Basset::inline('assets')->add('template', 'template.css')->forget();
~~~~

## Caching
Basset uses Laravel's inbuilt caching mechanisms, so the settings you have defined there will apply to Basset. Caching can be enabled globally in the
configuration file or on a per asset basis.

~~~~
Basset::inline('assets')->add('template', 'template.css')->remember();
~~~~

Or for your routes:

~~~~
Basset::styles('example', function($basset)
{
	$basset->add('website', 'website.css')
		   ->remember();
});
~~~~

The cached copy will be used if it is available otherwise a new copy will be generated and cached.

Want to specify a different amount of time to compress the assets for? Just pass the number of minutes you wish to cache them for as a parameter.

~~~~
Basset::inline('assets')->add('things', 'things.css')->remember(60); // Will remember the assets for 60 minutes
~~~~

### Clearing the Cache
If you need to remove an asset from the cache you can use the forget method.

~~~~
Basset::inline('assets')->add('things', 'things.css')->forget();
~~~~

This only clears the cache for the current Basset container.

## LESS
Basset ships with [LessPHP](http://leafo.net/lessphp/) to allow compiling of `.less` files without having LESS installed on your server. Once you enable LESS in the configuration file, simply start using your `.less` files.

~~~~
Basset::styles('example', function($basset)
{
	$basset->add('website', 'website.less');
});
~~~~

## Bundle Assets
Basset easily allows you to link to your bundles assets without much fuss at all. Simply prefix your assets with the bundle identifier and you're on your way.

~~~~
Basset::styles('example', function($basset)
{
	$basset->add('example::website', 'website.css');
});
~~~~

The `website.css` file will be loaded from `public/bundles/example/css/website.css`

## Directories
Since Basset 1.3 it's possible to specify directories from which to load assets. Let's start with the basics. When you don't supply any directory for your file name Basset will detect the extension and assume the directory to be either `css` or `js`. You can specify a custom directory relative to the public directory:

~~~~
Basset::styles('example', function($basset)
{
	$basset->add('website', 'assets/css/website.css'); // Located at: public/assets/css/website.css
});
~~~~

What if we have a heap of assets inside our `assets/css` directory and we don't want to write that every time. Easy. You define a directory.

~~~~
Basset::styles('example', function($basset)
{
	$basset->directory('public/assets/css', function($basset)
	{
		$basset->add('website', 'website.css'); // Located at: public/assets/css/website.css
	});
});
~~~~

You may have noticed that we needed to specify the `public` directory, that's because you can now define directories outside of the public directory.

What about bundles you say? We've thought of that. Let's say your bundle has a heap of assets. Adding them the old way is quite a pain.

~~~~
Basset::styles('example', function($basset)
{
	$basset->add('main', 'example::assets/css/main.css') // Located at: public/bundles/example/assets/css/website.css
	       ->add('links', 'example::assets/css/links.css')
	       ->add('styles', 'example::assets/css/styles.css')
	       ->add('tables', 'example::assets/css/tables.css')
	       ->add('forms', 'example::assets/css/forms.css');
});
~~~~

What if we were to change where these assets are located? We'd have to change every line, could become annoying. Instead we can use directories!

~~~~
Basset::styles('example', function($basset)
{
	$basset->directory('example::assets/css', function($basset)
	{
		$basset->add('main', 'main.css') // Located at: public/bundles/example/assets/css/website.css
	       ->add('links', 'links.css')
	       ->add('styles', 'styles.css')
	       ->add('tables', 'tables.css')
	       ->add('forms', 'forms.css');
	});
});
~~~~

That looks a whole lot nicer. Don't be afraid to mix and match directories and regular assets.

~~~~
Basset::styles('example', function($basset)
{
	$basset->directory('example::assets/css', function($basset)
		   {
		       $basset->add('main', 'main.css') // Located at: public/bundles/example/assets/css/website.css
	       	          ->add('links', 'links.css');
		   })
		   ->add('forms', 'styles/forms.css')
		   ->add('tables', 'styles/extra/tables.css');
});
~~~~

## In Closing
This is a fairly in-depth walk through on using the features of Basset. Feel free to dig into the source. You can also pop over to
[http://jasonlewis.me/code/basset](http://jasonlewis.me/code/basset) and check out the docs and some more examples.

If you have any questions you can get in touch with me via [my website](http://jasonlewis.me) or by sending my a message on here.

## Credits
I'd like to acknowledge a few people here.

- Taylor, for creating [Laravel](http://laravel.com) (and some of Basset is based off the Asset class)
- Stephen Clay, for the CSS Compressor, URI Rewriter, and JSMin packages.
- Ryan Grove, for the JSMin package.
- Douglas Crockford, for the JSMin package.
- Leaf Corcoran, for [LessPHP](http://leafo.net/lessphp/)

Thanks guys!

## Copyright and License
Basset was written by Jason Lewis for the Laravel framework.
Basset is released under the MIT License. See the LICENSE file for details.

Copyright 2011-2012 Jason Lewis

## Changelog

### Basset 1.3.2
- Bug fix with CSS URIs not being rewritten correctly causing badly formed links to images.

### Basset 1.3.1
- Bug fix with compression, compiling, and inline assets not working.

### Basset 1.3
- Updated API, routes are now defined with Basset::styles() and Basset::scripts()
- LESS support has been reintegrated.
- Directory support.
- Compiling support.

### Basset 1.2
- Now ships as a bundle for Laravel 3.
- Better route based loading support.
- Removal of LESS support for the time being.

### Basset 1.1
- Added LESS compatibility.
- Bug fix in asset dependency sorting.
- Dependency is now not type-limited.
- General code cleanup.

### Bassset 1.0
- Initial release of Basset.