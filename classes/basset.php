<?php

/**
 * Basset
 *
 * Basset is a better asset management bundle for the Laravel PHP framework. Basset allows you to
 * generate asset routes which can be compressed and cached to maximize website performance. Basset
 * also allows compressed and cached assets to appear inline.
 *
 * @package 	Basset
 * @version     1.4.2
 * @author 		Jason Lewis
 * @copyright 	2011-2012 Jason Lewis
 * @link		http://jasonlewis.me/code/basset
 */

class Basset {

	/**
	 * @var array $containers
	 */
	public static $containers = array();

	/**
	 * @var array $available
	 */
	public static $available = array(
		'css' => array(
			'group' 	=> 'styles',
			'extension' => 'css'
		),
		'less' => array(
			'group' 	=> 'styles',
			'extension' => 'css'
		),
		'sass' => array(
			'group'		=> 'styles',
			'extension' => 'css'
		),
		'js' => array(
			'group' 	=> 'scripts',
			'extension' => 'js'
		)
	);

	/**
	 * inline
	 * 
	 * Create a new inline Basset_Container instance or return an existing instance.
	 * 
	 * @param  string  $name
	 * @return Basset_Container
	 */
	public static function inline($name)
	{
		$name = 'inline::' . $name;

		if(isset(static::$containers[$name]))
		{
			return static::$containers[$name];
		}

		static::$containers[$name] = new Basset\Container;

		return static::$containers[$name]->inline();
	}

	/**
	 * valid
	 * 
	 * Iterate through the available formats and return the valid extension.
	 * 
	 * @param  string  $group
	 * @return mixed
	 */
	protected static function valid($group)
	{
		foreach(static::$available as $available)
		{
			if($group == $available['group'])
			{
				return $available['extension'];
			}
		}

		return false;
	}

	/**
	 * corrector
	 * 
	 * Corrects the end path to be used by Basset.
	 * 
	 * @param  string  $path
	 * @return string
	 */
	public static function corrector($path)
	{
		return substr(str_replace(URL::base(), '', $path), 1);
	}

	/**
	 * route
	 * 
	 * Return the route for the given name and extension.
	 * 
	 * @param  string  $name
	 * @param  string  $group
	 * @return string
	 */
	protected static function route($name, $group)
	{
		$groups = array(
			'styles'  => 'css',
			'scripts' => 'js'
		);

		return Bundle::option('basset', 'handles') . '/' . $name . '.' . $groups[$group];
	}

	/**
	 * development
	 * 
	 * Renders a containers assets individually as HTML tags. No compression or caching is
	 * applied to any of the assets.
	 * 
	 * @param  string  $container
	 * @return string  $group
	 */
	public static function development($container, $group = null)
	{
		if(str_contains($container, '.'))
		{
			$container = substr($container, 0, strpos($container, '.'));
		}

		if(array_key_exists($container, static::$containers))
		{
			if(is_null($group))
			{
				if(array_key_exists(static::route($container, 'styles'), static::$containers))
				{
					$group = 'styles';
				}
				elseif(array_key_exists(static::route($container, 'scripts'), static::$containers))
				{
					$group = 'scripts';
				}
			}

			return static::$containers[static::route($container, $group)]->development();
		}
		else
		{
			return '<!-- Basset could not find container [' . $container . '] -->';
		}
	}

	/**
	 * __callStatic
	 *
	 * Invokes one of the available containers and generates a new route.
	 *
	 * @param  string  $group
	 * @param  array   $arguments
	 * @return Basset_Container
	 */
	public static function __callStatic($group, $arguments)
	{
		if($extension = static::valid($group))
		{
			list($name, $callback) = $arguments;

			if(str_contains($name, '.'))
			{
				list($name, $extension) = explode('.', $name);
			}

			$route = static::route($name, $group);

			$assets = static::$containers[$route] = new Basset\Container($group);

			call_user_func($callback, $assets);

			Route::get($route, function() use ($assets)
			{
				return $assets;
			});
		}
		else
		{
			throw new BadMethodCallException('Could not find group [' . $group . '] on Basset.');
		}
	}

}