<?php namespace Basset;

class Config {

	/**
	 * @var array $extend
	 */
	public static $extend = array();

	/**
	 * @var array $config
	 */
	protected static $config = array();

	/**
	 * extend
	 * 
	 * Extend the configuration with a custom configuration file.
	 * 
	 * @param array $extend
	 */
	public static function extend($extend)
	{
		if(is_string($extend))
		{
			$extend = \Laravel\Config::get($extend);
		}

		static::$extend = $extend;
	}


	/**
	 * load
	 * 
	 * Loads the config and merges in defaults and any extenders.
	 */
	public static function load()
	{
		static::$config = array_merge(\Laravel\Config::get('basset::basset'), array(
			'caching'	  => array('forget' => false),
			'inline'	  => false,
			'development' => false
		), Config::$extend);
	}

	/**
	 * get
	 * 
	 * Get a config key from the config array.
	 * 
	 * @param  string  $key
	 * @return mixed
	 */
	public static function get($key)
	{
		return array_get(static::$config, $key);
	}

	/**
	 * set
	 * 
	 * Set a config key in the config array.
	 * 
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public static function set($key, $value)
	{
		array_set(static::$config, $key, $value);
	}

}