<?php namespace Basset;

use Laravel\Cache as C;

class Cache {

	/**
	 * The route that the cached assets respond to.
	 * 
	 * @var string
	 */
	protected $route;

	/**
	 * If the cached copy is set to be forgotten.
	 * 
	 * @var bool
	 */
	protected $forget;

	/**
	 * The name of the cached copy, also used for the compiled files.
	 * 
	 * @var string
	 */
	protected $name;

	/**
	 * The time the cached copy will be stored for.
	 * 
	 * @var int
	 */
	public $time;

	/**
	 * Create a new Basset\Cache instance.
	 * 
	 * @param  string  $route
	 * @return void
	 */
	public function __construct($route)
	{
		$this->route = $route;
	}

	/**
	 * Checks if the current group of assets has a cached copy. If the assets are set to be
	 * forgotten the cached copy will not be returned.
	 *
	 * @return bool
	 */
	public function exists($forget)
	{
		$name = $this->name();

		if(($exists = C::has($name)) and $forget)
		{
			C::forget($name);

			// We don't want to return the cached assets because we cleared
			// the cache and we want a new fresh copy of the assets returned.
			return false;
		}

		return $exists;
	}

	/**
	 * Get a cached copy of the group of assets.
	 *
	 * @return string|bool
	 */
	public function get()
	{
		if($this->exists())
		{
			return C::get($this->name());
		}

		return false;
	}

	/**
	 * Stores the assets in the cache for a set amount of time.
	 *
	 * @param  string  $assets
	 * @return void
	 */
	public function store($assets)
	{
		if(!$this->exists())
		{
			C::put($this->name(), $assets, $this->time);
		}
	}

	/**
	 * Determines the name of the cached assets.
	 *
	 * @return string
	 */
	public function name()
	{
		if(!is_null($this->name))
		{
			return $this->name;
		}

		return $this->name = md5('basset::' . $this->route);
	}
}