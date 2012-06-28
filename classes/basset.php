<?php

/**
 * Basset is a better asset management bundle for Laravel.
 *
 * @package 	Basset
 * @version     2.0.0
 * @author 		Jason Lewis
 * @copyright 	2011-2012 Jason Lewis
 * @link		http://jasonlewis.me/code/basset
 */

class Basset {

	/**
	 * Array of registered route containers.
	 * 
	 * @var array
	 */
	protected static $routes = array();

	/**
	 * Array of registered inline containers.
	 * 
	 * @var array
	 */
	protected static $inline = array();

	/**
	 * Register a new inline container.
	 * 
	 * @param  string  $name
	 */
	public static function inline($name)
	{
		$name = 'inline::' . $name;

		if(array_key_exists($name, static::$inline))
		{
			return static::$inline[$name];
		}

		static::$inline[$name] = new Basset\Container;

		return static::$inline[$name]->inline();
	}

	/**
	 * Return the route to be handled.
	 *
	 * @param  string   $name
	 * @param  string   $group
	 * @return string
	 */
	protected static function route($name, $group)
	{
		$extensions = array(
			'styles'  => 'css',
			'scripts' => 'js'
		);

		return Bundle::option('basset', 'handles') . '/' . $name . '.' . $extensions[$group];
	}

	/**
	 * Creates a new container to register assets or uses an already existing container.
	 * 
	 * @param  string   $name
	 * @param  string   $group
	 * @param  Closure  $callback
	 * @return void
	 */
	protected static function assets($name, $group, Closure $callback)
	{
		$route = static::route($name, $group);

		if(!array_key_exists($route, static::$routes))
		{
			static::$routes[$route] = new Basset\Container($route, $group);
		}

		call_user_func($callback, static::$routes[$route]);

		// Register a blank route so we don't get nasty 404's thrown at us when we attempt
		// to display the compiled assets.
		Route::get($route, function(){});
	}

	/**
	 * Shares an asset that can be later added without too much typing.
	 * 
	 * @param  string  $name
	 * @param  string  $file
	 * @return void
	 */
	public static function share($name, $file)
	{
		Basset\Container::$shared[$name] = $file;
	}

	/**
	 * Generate a scripts route.
	 * 
	 * @param  string   $name
	 * @param  Closure  $callback
	 * @return void
	 */
	public static function scripts($name, Closure $callback)
	{
		static::assets($name, 'scripts', $callback);
	}

	/**
	 * Generate a styles route.
	 * 
	 * @param  string   $name
	 * @param  Closure  $callback
	 * @return void
	 */
	public static function styles($name, Closure $callback)
	{
		static::assets($name, 'styles', $callback);
	}

	/**
	 * Compiles assets for all registered containers.
	 * 
	 * @return void
	 */
	public static function compile()
	{
		foreach(static::$routes as $route)
		{
			$route->compile();
		}
	}

	/**
	 * Return the compiled output for a given container.
	 * 
	 * @return string
	 */
	public static function compiled()
	{
		$hash = md5('basset::' . URI::current());

		// Cache is the first priority, if a cached copy exists then Basset will return
		// it before anything else.
		if(Cache::has($hash))
		{
			return Cache::get($hash);
		}
		// If there is no cached copy Basset will look for a compiled copy in the
		// compiled directory and return it if it exists.
		elseif(File::exists($path = Basset\Config::get('compiling.directory') . DS . $hash))
		{
			return File::get($path);
		}

		// If nothing could be found we'll let them know by simply returning a not found
		// error to the browser.
		return '/* Basset could not load [' . URI::current() . '] */';
	}

	/**
	 * Return the URL to the asset route container.
	 * 
	 * @param  string  $route
	 * @return string
	 */
	public static function show($route)
	{
		$methods = array(
			'css' => 'style',
			'js'  => 'script'
		);

		return HTML::$methods[File::extension($route)](URL::to_asset(Bundle::option('basset', 'handles') . '/' . $route));
	}

}