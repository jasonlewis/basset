<?php namespace JasonLewis\Basset;

use Closure;
use Illuminate\Config\Repository;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Filesystem\Filesystem;

class Factory {

	/**
	 * Asset collections.
	 * 
	 * @var array
	 */
	protected $collections;

	/**
	 * Illuminate filesystem instance.
	 * 
	 * @var Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Illuminate config repository instance.
	 * 
	 * @var Illuminate\Config\Repository
	 */
	protected $config;

	/**
	 * Illuminate url generator instance.
	 * 
	 * @var Illuminate\Routing\UrlGenerator
	 */
	protected $url;

	/**
	 * Basset asset manager instance.
	 * 
	 * @var JasonLewis\Basset\AssetManager
	 */
	protected $manager;

	/**
	 * Create a new factory instance.
	 * 
	 * @param  Illuminate\Filesystem\Filesystem  $files
	 * @param  Illuminate\Config\Repository  $config
	 * @param  Illuminate\Routing\UrlGenerator  $url
	 * @param  JasonLewis\Basset\AssetManager  $manager
	 * @return void
	 */
	public function __construct(Filesystem $files, Repository $config, UrlGenerator $url, AssetManager $manager)
	{
		$this->files = $files;
		$this->config = $config;
		$this->url = $url;
		$this->manager = $manager;
	}

	/**
	 * Alias of Factory::collection()
	 * 
	 * @param  string  $name
	 * @param  Closure  $callback
	 * @return JasonLewis\Basset\Factory
	 */
	public function make($name, Closure $callback = null)
	{
		return $this->collection($name, $callback);
	}

	/**
	 * Create or return an existing collection.
	 * 
	 * @param  string  $name
	 * @param  Closure  $callback
	 * @return JasonLewis\Basset\Factory
	 */
	public function collection($name, Closure $callback = null)
	{
		if ( ! isset($this->collections[$name]))
		{
			$this->collections[$name] = new Collection($this->files, $this->config, $this->manager, $name);
		}

		// If the collection was given a callable function where assets can be
		// added we'll fire it now.
		if (is_callable($callback))
		{
			call_user_func($callback, $this->collections[$name]);
		}

		return $this->collections[$name];
	}

}