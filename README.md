# Basset

Basset is a Better Asset manager for the Laravel PHP framework. Basset allows you to generate asset routes which can be compressed and cached to maximize website performance. Basset also allows compressed and cached assets to appear inline.

- **Author:** Jason Lewis
- **Website:** [http://jasonlewis.me/code/basset](http://jasonlewis.me/code/basset)
- **Version:** 1.2

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

## Basics
Much of the Laravel Asset class functionality is retained in Basset since Basset is based partly off this class. There is little to configure out of the box unless you want to
enable global compression and caching for all assets.

Inline assets is pretty important, let's take a look at a composer which might add a couple of assets.

~~~~
View::compose('layout', function($view)
{
	Basset::inline('assets')
		->add('bootstrap', 'bootstrap.js')
		->add('ie6', 'ie6.css');
});
~~~~

The `Basset::inline()` method accepts a single parameter which will be the name of the container. Now in our layout view we can render the assets. We might want the inline CSS in our head and the inline JS at the bottom of our body.

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

### Dependencies
An optional third parameter is available for dependency sorting. You can give the name of an asset that it will depend on or an array of names.

~~~~
Basset::inline('assets')->add('template', 'template.css', 'things')->add('things', 'things.css');
~~~~

Now **template** depends on **things**, so it will be loaded after **things** has been loaded.
If no dependency is given then assets are loaded based on the order they are listed.

### External Assets
In some cases you may want to link to external assets, such as jQuery hosted by Google. Don't stress, simply write the full URL and Basset will
handle it for you.

**Note: If the asset is unable to load it may cause problems with other assets, this issue will be addressed soon.**

## Route Based Loading
So far I've been showing you how to use inline assets, but the best part about Basset is the route based loading.

Let's take a look at some routes. Open up **bundles/basset/routes.php** and look at the example CSS route.

~~~~
Basset::css('example', function($basset)
{
	$basset->add('normalize', 'normalize.css')
		   ->add('website', 'website.css');
});
~~~~

It's that easy. We don't worry about the full route or including the extension, just the name of the route and the Basset callback.

Displaying the assets is also just as easy. In the head of your document just use Laravel's HTML class to load your Basset route:

~~~~
echo HTML::style('/basset/example.css');
~~~~

Remember, JavaScript files work the same. Just replace **css** with **js** when defining the route.

## Compression
You can compress both inline and route based assets. To globally enable compression just set the option in the configuration file. To set the option on a per asset basis you can do it like so:

~~~~
Basset::inline('assets')->add('template', 'template.css')->compress();
~~~~

Or for your routes:

~~~~
Basset::css('example', function($basset)
{
	$basset->add('normalize', 'normalize.css')
		   ->add('website', 'website.css')
		   ->compress();
});
~~~~

**Read before your compressing your assets!**

When developing a local application there is no need to be compressing assets. When your application becomes live that's when compression should be used and only in conjunction with caching.

## Caching
Basset uses Laravel's inbuilt caching mechanisms, so the settings you have defined there will apply to Basset. Caching can be enabled globally in the
configuration file or on a per asset basis.

~~~~
Basset::inline('assets')->add('template', 'template.css')->remember();
~~~~

Or for your routes:

~~~~
Basset::css('example', function($basset)
{
	$basset->add('normalize', 'normalize.css')
		   ->add('website', 'website.css')
		   ->remember();
});
~~~~

The cached copy will be used if it is available otherwise a new copy will be generated and cached.

Want to specify a different amount of time to compress the assets for? Just pass the number of minutes you wish to cache them for as a parameter.

~~~~
Basset::inline('assets')->add('template', 'template.css')->remember(60); // Will remember the assets for 60 minutes
~~~~

### Clearing the Cache
If you need to remove an asset from the cache you can use the forget method.


~~~~
Basset::inline('assets')->add('template', 'template.css')->forget();
~~~~

This only clears the cache for the current Basset container.

## In Closing
This is a fairly in-depth walk through on using the features of Basset. Feel free to dig into the source. You can also pop over to
[http://jasonlewis.me/code/basset](http://jasonlewis.me/code/basset) and check out the docs, examples, and the API.

If you have any questions you can get in touch with me via [my website](http://jasonlewis.me) or by sending my a message on here.

## Credits
I'd like to acknowledge a few people here.

- Taylor, for creating [Laravel](http://laravel.com) (and some of Basset is based off the Asset class)
- Stephen Clay, for the CSS Compressor, URI Rewriter, and JSMin packages.
- Ryan Grove, for the JSMin package.
- Douglas Crockford, for the JSMin package.

Thanks guys!

## Copyright and License
Basset was written by Jason Lewis for the Laravel framework.
Basset is released under the MIT License. See the LICENSE file for details.

Copyright 2011 Jason Lewis

## Changelog
**Version 1.2 - 9th April 2012**

- Now ships as a bundle for Laravel 3.
- Better route based loading support.
- Removal of LESS support for the time being.

**Version 1.1 - 15th September 2011**

- Added LESS compatibility.
- Bug fix in asset dependency sorting.
- Dependency is now not type-limited.
- Re-write of a few internal methods, no API breaking changes.

**Version 1.0 - 2nd September 2011**

- Initial release