<?php namespace Basset;

use Laravel\Config as C;

class Config {

	/**
	 * Array containing the extended configuration.
	 * 
	 * @var array
	 */
	public static $extend = array();

	/**
	 * Array containing the configuration.
	 * 
	 * @var array
	 */
	protected static $config = array();

	/**
	 * Extend the configuration with a custom configuration file.
	 * 
	 * @param  array  $extend
	 * @return void
	 */
	public static function extend($extend)
	{
		if(is_string($extend))
		{
			$extend = C::get($extend);
		}

		static::$extend = $extend;
	}


	/**
	 * Loads the config and merges in defaults and any extenders.
	 * 
	 * @return void
	 */
	public static function load()
	{
		static::$config = array_merge(C::get('basset::basset'), array(
			'caching'	  => array('forget' => false),
			'inline'	  => false,
			'development' => false
		), Config::$extend);
	}

	/**
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