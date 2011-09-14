# Basset
Basset is a Better Asset class for the Laravel framework. Basset is a module which means it accepts route based loading. This allows
you to maintain multiple assets yet combine and compress your code.

Basset features route based loading, compression, combining, pathing, caching of assets, and LESS compatibility.

- **Author:** Jason Lewis
- **Website:** [http://jasonlewis.me/projects/basset](http://jasonlewis.me/projects/basset)
- **Version:** 1.1

## Installation

1. Clone or download a tarball of Basset.
2. Place the **basset** directory inside your **modules** directory.
3. Open the **modules/basset/config/basset.php** config file and make changes where you see fit.
4. Open your **application/config/application.php** file and add 'basset' to your active modules array.
5. Begin using Basset!

## Basics
Basset is designed to be as flexible as possible. It retains much of the standard Asset functionality and is partly based off this
class. By default the default path in the configuration file will be nothing. This is because Laravel ships with the css and js directories
in the public directory. Adding files is dead simple:

    Basset\Basset::add('template', 'template.css');

The first parameter is the name of the asset and the second is the filename. Notice how the filename is lacking the css directory?
That's because Basset looks at the extension and uses that to determine the directory to look in. This would load **public/css/template.css** because
that's the default path.

To add multiple assets you can use chained calls:

	Basset\Basset::add('template', 'template.css')->add('things', 'things.css');

Let's assume that we did that in our **application/composers.php** file. Now we want to actually render our assets in our template. Easy.

    echo Basset\Basset::styles();

Would produce:

    <link href="http://yourwebsite/public/css/template.css" rel="stylesheet" media="all" type="text/css">
    <link href="http://yourwebsite/public/css/things.css" rel="stylesheet" media="all" type="text/css">

It's that simple. Maybe you want to display them inline instead? No problem.

    echo Basset\Basset::inline()->styles();

That's all you need. And it doesn't matter in which order you specify them, either way is fine!

So far all we've been talking about is styles, what about JavaScript? Basset has that covered too. Instead of using **Basset::styles()** just use **Basset::scripts()**
Adding JavaScript assets is exactly the same, except you're using a .js extension.

### Dependencies
An optional third parameter is available for dependency sorting. You can give the name of an asset that it will depend on or an array of names.

    Basset\Basset::add('template', 'template.css', 'things')->add('things', 'things.css');

Now the **template** asset depends on **things**, so it will be loaded after **things** has been loaded.
If no dependency is given then assets are loaded based on the order they are listed.

You can tell Basset to make a stylesheet depend on a script and vice versa. All you need to do is prefix the assets name with its type and Basset
will sort it out for you.

    Basset\Basset::add('some_javascript', 'some_javascript.js', 'style::template')->add('template', 'template.css');

For scripts, use **script::asset_name** instead of style.

### External Assets
In some cases you may want to link to external assets, such as jQuery hosted by Google. Don't stress, simply write the full URL and Basset will
handle the rest!

So now you've got some basics down let's dig into the real fun stuff.

## Containers ##
Like Laravel's Asset class, Basset allows you to use containers to group selected assets. Containers are only useful when using inline loading or
the standard element based loading. For example you may have a some JavaScript assets you want linked to the standard HTML way and a few others
that you want inline. This is where containers come in handy:

    Basset::container('js_standard')->add('jquery', 'jquery.js');
    Basset::container('js_inline')->add('initialize', jquery.initialize.js');

Then in your View, you can display them as such:

    echo Basset::container('js_standard')->scripts();
    echo Basset::container('js_inline')->inline()->scripts();

Piece of cake.

## Route Based Loading
The beauty of Basset being a module means it allows for route based loading. The added benefit of this is allowing combining and compressing of
assets.

There are a few things you must first note when using route based loading.

1. Assets will *always* be combined regardless of the setting in the configuration file.
2. You need a separate route for both styles and scripts.

This means that all your asset logic will be separated from your application and is easily manageable.

Let's crack on. Open up the **modules/basset/routes.php** file and you'll see an example route:

    'GET /example/template.css' => function()
    {
        return Basset::add('reset', 'reset.css')
            ->add('template', 'template.css');
    }

It's that easy.

You may have noticed that the URI has a **.css** extension. This allows a filter to catch the extension and set the appropriate content type
for the returned text. You don't have to do it like this though, you can call one of our after filters like so:

    'GET /example/template' => array('after' => 'css', function()
    {
        return Basset::add('reset', 'reset.css')
            ->add('template, 'template.css');
    })

The above will do the exact same thing. Personally it's easier to just end the route with the appropriate extension. Of course for JavaScript
files you'd just use **.js** instead.

Rendering the files is easy. In the head of your document just use Laravel's HTML class to load your Basset:

    echo HTML::style('/basset/example/template.css');

Remember, JavaScript files work the same. Just replace **style** with **script** and **.css** with **.js**

## Pathing
Another great thing about Basset is the simplicity of pathing. You must supply a default path in the configuration file. This default
path will be used when you don't supply a path for your assets. Paths work by defining a name of your path and the actual path *relative* to the public directory.

Let's assume you have some assets setup like this:

    public/
        assets/
            /css
            /js
            /website
                /css
                    /reset.css
                /js
            /store
                /css
                    /template.css
                /js

You can then set this path structure up in your configuration file:

    'paths' => array(
        'default'	=> 'assets',
        'website' 	=> 'assets/website',
        'store'		=> 'assets/store'
    )

Let's use our route based loading example from earlier, but this time load the **reset.css** from the **website** path and the
**template.css** from the **store** path.

    'GET /example/template.css' => function()
    {
        return Basset::add('reset', 'website::reset.css')
            ->add('template, 'store::template.css');
    }

That's all there is to it. Simply prefix the path name to the asset name and it'll load it for you.

Paths can also be added at runtime, although it's recommended you add them in your configuration file. That way you don't have to go
through your routes and alter them if you change your directory structure. However, to add paths at runtime simply call the **Basset::path()** method.

    'GET /example/template.css' => function()
    {
        return Basset::path('website', 'assets/website')
            ->path('store', 'assets/store')

            // Now we can add our assets to the just added paths:
            ->add('reset', 'website::reset.css')
            ->add('template, 'store::template.css');
    }

## Combining and Compressing Assets
To use the combining and compressing functionality of Basset you must either use inline styling or route based loading.

By default when you use route based loading assets will automatically be combined into a single file. This occurs regardless of the setting in your
configuration file. Assets will not be compressed unless you explicitly turn on compression or call the **Basset::compress()** method.

    Basset\Basset::add('reset', 'reset.css')->add('template', 'template.css')->compress()->inline();

Compression will combine the files for you, so there is no need to call the combine method.

It's possible to combine files during without compression by using the **Basset::combine()** method.

    Basset\Basset::add('reset', 'reset.css')->add('template', 'template.css')->combine()->inline();

In these examples we're using the **Basset::inline()** method to achieve the combining and compression of both files.

**Read before your compress!**

Generally you *should not* compress your assets until you are deploying a live website. Compressing your files every page load isn't a good thing
and you'll only gain a benefit using it when you compress and cache your assets. It's unlikely you'll want to cache your assets during development
so it's best to only compress on a live website.

## Caching
Basset uses Laravel's inbuilt caching class, so the settings you have defined there will apply to Basset. Caching can be enabled globally in the
configuration file (as well as the **cache_for** setting which is the number of minutes to cache the assets) or runtime on specific assets. This
can be useful when certain parts of a website go live prior to other parts and caching may be required for some items.

To cache assets, simply use the **Basset::remember()** method.

    Basset\Basset::add('template', 'template.css')->compress()->remember();

On the first page load the asset will be compressed and cached. Further page loads will result in the cached copy being loaded instead of the
asset being re-compressed every time.

Want to specify a different amount of time to compress the assets for? Just pass the number of hours you wish to cache them for as a parameter.

    Basset\Basset::add('template', 'template.css')->compress()->remember(1);

Like combining and compressing, caching can also be applied to inline assets.

**Note:** Once assets are cached the cached copy will *always* be loaded. Disabling caching simply prevents further assets from being cached. To
stop using the cached copy you must clear it.

### Clearing the Cache
There may be times when you need to force a reset of the cache to add a new cached copy or to stop using a cached copy. The **Basset::forget()** method
does just that.

    Basset\Basset::add('template', 'template.css')->forget();

The cache will now be cleared and the new copy returned. This will only clear the cache for the current Basset container.

## LESS
Basset 1.1 ships with LESS compatibility out of the box. If you have LESS installed or are using the client-side version you're good to go, just start linking to your LESS stylesheets.

    Basset\Basset::add('template', 'template.less');

Basset will detect your LESS stylesheet and will return the properly formatted LESS tags.

    <link href="http://yourwebsite/public/less/template.less" rel="stylesheet/less" type="text/css" media="all">

LESS stylesheets can also be used with route based loading.

If you don't have the LESS compiler installed or aren't using the client-side version yet you still want to take full advantage of the LESS beauty you can use Bassets internal LESS compiler.
Basset uses the [LessPHP](http://leafo.net/lessphp/) compiler internally to compile your LESS stylesheets. To enable it open up **modules/basset/config/basset.php** and look for the LESS related
settings. Set the `php_compiler` to `true` and you're good to go.

*Note:* Compiling LESS stylesheets using the internally compiler may have an effect on performance if used on heavy traffic websites. Use at your own discretion.

## Shortening the Basset
It can be a real pain writing `Basset\Basset::add()` all the time, right? Don't worry you can shorten the call to whatever you want.

By default in the **modules/basset/routes.php** file you can use `Basset::add()`, however in your application you'll need to use the longer version unless you
explicitly shorten it. This can be done one of two ways.

Create a library in your **application/libraries** directory called **basset.php**. In this file all you need to do is extend the Basset\Basset class.

    class Basset extends Basset\Basset {}

Don't want to use Basset? Simply rename the file to whatever you chose, for example **b.php**. Update your file like so:

    class B extends Basset\Basset {}

Note that if you make deep-routes in the Basset module, you'll need to copy the `use Basset\Basset as Basset;` from the **modules/basset/routes.php** file.

## In Closing
This is a fairly in-depth walk through on using the features of Basset. Feel free to dig into the source. You can also pop over to
[http://jasonlewis.me/projects/basset](http://jasonlewis.me/projects/basset) and check out the docs, examples, and the API.

If you have any questions you can get in touch with me via [my website](http://jasonlewis.me) or by sending my a message on here.

## Credits
I'd like to acknowledge a few people here.

- Taylor, for creating [Laravel](http://laravel.com) (and some of Basset is based off the Asset class)
- Stephen Clay, for the CSS Compressor, URI Rewriter, and JSMin packages.
- Ryan Grove, for the JSMin package.
- Douglas Crockford, for the JSMin package.
- Leafo, for [LessPHP](http://leafo.net/lessphp/)

Thanks guys!

## Copyright and License
Basset was written by Jason Lewis for the Laravel framework.
Basset is released under the MIT License. See the LICENSE file for details.

Copyright 2011 Jason Lewis

## Changelog
**Version 1.1 - 15th September 2011**

- Added LESS compatibility.
- Bug fix in asset dependency sorting.
- Dependency is now not type-limited.
- Re-write of a few internal methods, no API breaking changes.

**Version 1.0 - 2nd September 2011**

- Initial release