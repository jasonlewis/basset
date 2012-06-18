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
		if(!array_key_exists(URI::current(), static::$routes))
		{
			return null;
		}

		$hash = md5('basset::' . URI::current());

		if(Cache::has($hash))
		{
			return Cache::get($hash);
		}
		elseif(File::exists(Basset\Config::get('compiling.directory') . DS . $hash))
		{
			return File::get(Basset\Config::get('compiling.directory') . DS . $hash);
		}

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