<?php namespace Basset;

/**
 * Basset
 *
 * Basset is a better asset management bundle for the Laravel PHP framework. Basset allows you to
 * generate asset routes which can be compressed and cached to maximize website performance. Basset
 * also allows compressed and cached assets to appear inline.
 *
 * @package 	Basset
 * @version     1.3.2
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

		static::$containers[$name] = new Basset_Container;

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

			$route = Bundle::option('basset', 'handles') . '/' . $name . '.' . $extension;

			Route::get($route, function() use ($callback, $name, $group, $extension)
			{
				call_user_func($callback, $assets = new Basset_Container($group));

				Basset::$containers[$name] = $assets;

				return $assets;
			});
		}
		else
		{
			throw new BadMethodCallException('Could not find group [' . $group . '] on Basset.');
		}
	}

}