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
	 * Path to public directory.
	 * 
	 * @var string
	 */
	protected $publicPath;

	/**
	 * Create a new factory instance.
	 * 
	 * @param  Illuminate\Filesystem\Filesystem  $files
	 * @param  Illuminate\Config\Repository  $config
	 * @param  Illuminate\Routing\UrlGenerator  $url
	 * @param  string  $publicPath
	 * @return void
	 */
	public function __construct(Filesystem $files, Repository $config, UrlGenerator $url, $publicPath)
	{
		$this->files = $files;
		$this->config = $config;
		$this->url = $url;
		$this->publicPath = $publicPath;
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
			$this->collections[$name] = new Collection($name, $this->files, $this->config, $this->publicPath);
		}

		// If the collection was given a callable function where assets can be
		// added we'll fire it now.
		if (is_callable($callback))
		{
			call_user_func($callback, $this->collections[$name]);
		}

		return $this->collections[$name];
	}

	/**
	 * Get the path the public directory.
	 * 
	 * @return string
	 */
	public function getPublicPath()
	{
		return $this->publicPath;
	}

}