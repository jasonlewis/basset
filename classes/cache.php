<?php namespace Basset;

class Cache {

	/**
	 * @var array $assets
	 */
	protected $assets = array();

	/**
	 * @var string $group
	 */
	protected $group;

	/**
	 * @var bool $forget
	 */
	protected $forget;

	/**
	 * @var string $name
	 */
	protected $name;

	/**
	 * @var int $time
	 */
	public $time;

	/**
	 * register
	 *
	 * Registers the assets and the group that is being rendered.
	 *
	 * @param  array   $assets
	 * @param  string  $group
	 * @param  bool    $forget
	 */
	public function register($assets, $group, $forget)
	{
		$this->assets = $assets;

		$this->group = $group;

		$this->forget = $forget;
	}

	/**
	 * has
	 *
	 * Checks if the current group of assets has a cached copy. If the assets are set to be
	 * forgotten the cached copy will not be returned.
	 *
	 * @return bool
	 */
	public function has()
	{
		$name = $this->name();

		if(($has = \Laravel\Cache::has($name)) && $this->forget)
		{
			\Laravel\Cache::forget($name);

			// We don't want to return the cached assets because we cleared
			// the cache and we want a new fresh copy of the assets returned.
			return false;
		}

		return $has;
	}

	/**
	 * get
	 *
	 * Get a cached copy of the group of assets.
	 *
	 * @return mixed
	 */
	public function get()
	{
		if($this->has())
		{
			$assets = \Laravel\Cache::get($name = $this->name());

			return $assets;
		}

		return false;
	}

	/**
	 * run
	 *
	 * Runs the cache and stores it if the cache has not already been set.
	 *
	 * @param  string  $assets
	 */
	public function run($assets)
	{
		if(!$this->has())
		{
			\Laravel\Cache::put($this->name(), $assets, $this->time);
		}
	}

	/**
	 * name
	 *
	 * Determines the cached name of the group of assets.
	 *
	 * @return string
	 */
	public function name()
	{
		if($this->name)
		{
			return $this->name;
		}

		$name = array();

		foreach($this->assets[$this->group] as $asset)
		{
			$name[] = str_replace(path('base'), '', $asset->source) . '/' . $asset->file;
		}

		sort($name);

		return $this->name =  md5('basset_' . $this->group . '_' . implode('', $name));
	}
}