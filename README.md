# Basset
Basset is a Better Asset class for the Laravel framework. Basset is a module which means it accepts route based loading. This allows
you to maintain multiple assets yet combine and compress your code.

Basset features route based loading, compression, combining, pathing, and caching of assets.

- **Author:** Jason Lewis
- **Website:** [http://jasonlewis.me/projects/basset](http://jasonlewis.me/projects/basset)
- **Version:** 1.0

## Installation

1. Clone or download a tarball of Basset.
2. Place the **basset** directory inside your **modules** directory.
3. Copy the **modules/basset/basset.php** config file into your **application/config** directory. Make any changes to this file you see fit.
4. Open your **application/config/application.php** file and add 'basset' to your active modules array.
5. Begin using Basset!

## Basics
Basset is designed to be as flexible as possible. It retains much of the standard Asset functionality and is partly based off this
class. By default the default path in the configuration file will be nothing. This is because Laravel ships with the css and js directories
in the public directory. So let's add some files:

    Basset\Basset::add('template', 'template.css');

Simple, right? The first parameter is the name of the asset and the second is the filename. Notice how the filename is lacking the css directory?
That's because Basset looks at the extension and uses that to determine the directory to look in. This would load **public/css/template.css** because
that's the default path.

It's possible to chain methods to add multiple assets in one go.

	Basset\Basset::add('template', 'template.css')->add('things', 'things.css');

Let's assume that we did that in our **modules/basset/composers.php** file. Now we want to actually render our assets in our template. Easy.

    echo Basset\Basset::styles();

It's that simple. Maybe you want to display them inline instead? No problem.

    echo Basset\Basset::inline()->styles();

That's all you need. And it doesn't matter in which order you specify them, either way is fine!

So far all we've been talking about is styles, what about JavaScript? Basset has that covered too. Instead of using **Basset::styles()** just use **Basset::scripts()**
Adding JavaScript assets is exactly the same, except you're using a .js file extension.

### Dependencies
An optional third parameter is available for dependency sorting. You can give the name of an asset that it will depend on or an array of names.

    Basset\Basset::add('template', 'template.css', 'things')->add('things', 'things.css');

Now the **template** asset depends on **things**, so it will be loaded after **things** has been loaded.
If no dependency is given then assets are loaded based on the order they are listed.

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
2. You need a separate route for both CSS and JS assets.

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

Rendering the files is easy. In the head of your document just use Laravel's HTML class to load your asset:

    echo HTML::style('/basset/example/template.css');

Remember, JavaScript files work the same. Just replace **style** with **script** and **.css** with **.js**

## Pathing
Another great thing about Basset is the simplicity of pathing. By default you must supply a default path in the configuration file. This default
path will be used when you don't supply a path to use. Paths work by defining a name of your path and the actual path *relative* to the public directory.

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
            ->add('reset', 'website::reset.css')
            ->add('template, 'store::template.css');
    }

## Combining and Compressing Assets
To use the combining and compressing functionality of Basset you must either use inline styling or route based loading.

By default when you use route based loading assets will automatically be combined into a single file. This occurs regardless of the setting in your
configuration file. Assets will not be compressed unless you explicitly turn on compression or call the **Basset::compress()** method.

    echo Basset\Basset::add('reset', 'reset.css')->add('template', 'template.css')->compress()->inline();

Compression will combine the files for you.
It's possible to combine files during runtime without compression by using the **Basset::combine()** method.

    echo Basset\Basset::add('reset', 'reset.css')->add('template', 'template.css')->combine()->inline();

In these examples we're using the **Basset::inline()** method to achieve the combining and compression of both files.

**Just so you know...**
Generally you *should not* compress your assets until you are deploying a live website. Compressing your files every page load isn't a good thing
and you'll only gain a benefit using it when you compress and cache your assets. It's unlikely you'll want to cache your assets during development
so it's best to only compress on a live website.

## Caching
Basset uses Laravel's inbuilt caching class, so the settings you have defined there will apply to Basset. Caching can be enabled globally in the
configuration file (as well as the **cache_for** setting which is the number of minutes to cache the assets) or runtime on specific assets. This
can be useful when certain parts of a website go live prior to other parts and caching may be required for some items.

To cache assets, simply use the **Basset::remember()** method.

    return Basset::add('template', 'template.css')->compress()->remember();

On the first page load the asset will be compressed and cached. Further page loads will result in the cached copy being loaded instead of the
asset being re-compressed every time.

Like combining and compressing, caching can also be applied to inline assets.

**Note:** Once assets are cached the cached copy will *always* be loaded. Disabling caching simply prevents further assets from being cached. To
stop using the cached copy you must clear it.

### Clearing the Cache
There may be times when you need to force a reset of the cache to add a new cached copy or to stop using a cached copy. The **Basset::forget()** method
does just that.

    return Basset::add('template', 'template.css')->forget();

The cache will now be cleared and the new copy returned. This will only clear the cache for the current Basset container.

## Shortening the Basset
In some examples we're using `Basset\Basset::add()` and then in others just `Basset::add()`. By default in the **modules/basset/routes.php** file you can use the shortened version,
however in your application you'll need to use the longer version unless you explicitly shorten it. This can be done one of two ways.

### Creating a Library
Option one is to create a library in your **application/libraries** directory called **basset.php**. In this file simply include the following:

    class Basset extends Basset\Basset {}

### Aliasing the Basset class
Option two is slightly more involved and doesn't work for Views. However, in your **application/routes.php** (or wherever you're using Basset) you can add this at the start of your file.

    use Basset\Basset as Basset;

I normally place it directly after the opening of PHP. Like mentioned, this doesn't work in Views.

I prefer using the library method if I need to use Basset from within my application. It's only one line of code and is easy as pie.

## In Closing
This is a fairly in-depth walk through on using the features of Basset. Feel free to dig into the source. You can also pop over to
[http://jasonlewis.me/projects/basset](http://jasonlewis.me/projects/basset) and check out the docs, examples, and the API.

If you have any questions you can get in touch with me via [my website](http://jasonlewis.me) or by sending my a message on here.

## Credits
I'd like to acknowledge a few people here.

- Taylor, for creating Laravel (and some of Basset is based off the Asset class)
- Stephen Clay, for the CSS Compressor, URI Rewriter, and JSMin packages.
- Ryan Grove, for the JSMin package.
- Douglas Crockford, for the JSMin package.

Thanks guys!

## Copyright and License
Basset was written by Jason Lewis for the Laravel framework.
Basset is released under the MIT License. See the LICENSE file for details.

Copyright 2011 Jason Lewis

## Changelog
**Version 1.0 - 2nd September 2011**

- Initial release