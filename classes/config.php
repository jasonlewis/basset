<?php namespace Basset;

class Config {

	/**
	 * @var array $extend
	 */
	public static $extend = array();

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
	 * @var array $config
	 */
	protected $config;

	/**
	 * __construct
	 * 
	 * Create a new config object and fetch the config file, merging in any extensions and defaults.
	 */
	public function __construct()
	{
		$this->config = array_merge(\Laravel\Config::get('basset::basset'), array(
			'caching'	=> array('forget' => false),
			'inline'	=> false
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
	public function get($key)
	{
		return array_get($this->config, $key);
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
	public function set($key, $value)
	{
		array_set($this->config, $key, $value);
	}

}