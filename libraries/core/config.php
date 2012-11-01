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
	protected $config = array();

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

		static::$extend = (array) $extend;
	}


	/**
	 * Loads the config and merges in defaults and any extenders.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->config = array_merge(array(
			'caching'     => array('forget' => false),
			'inline'      => false,
			'development' => false
		), C::get('basset::basset'), Config::$extend);
	}

	/**
	 * Get a config key from the config array.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function get($key)
	{
		return array_get($this->config, $key);
	}

	/**
	 * Set a config key in the config array.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function set($key, $value)
	{
		array_set($this->config, $key, $value);
	}

}