<?php namespace JasonLewis\Basset;

use Illuminate\Filesystem\Filesystem;

class AssetManager {

	/**
	 * Illuminate filesystem instance.
	 * 
	 * @var Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Path to the public directory.
	 * 
	 * @var string
	 */
	protected $publicPath;

	/**
	 * Application working environment.
	 * 
	 * @var string
	 */
	protected $environment;

	/**
	 * Create a new asset manager instance.
	 * 
	 * @param  Illuminate\Filesystem\Filesystem  $files
	 * @param  string  $publicPath
	 * @param  string  $environment
	 * @return void
	 */
	public function __construct(Filesystem $files, $publicPath, $environment)
	{
		$this->files = $files;
		$this->publicPath = $publicPath;
		$this->environment = $environment;
	}

	/**
	 * Make a new asset instance, resolving the absolute and relative paths.
	 * 
	 * @param  string  $path
	 * @return JasonLewis\Basset\Asset
	 */
	public function make($path)
	{
		// If the path to the asset is a valid URL then we'll assume the asset is being
		// remotely hosted and so the absolute path will be the URL to the asset.
		$absolutePath = filter_var($path, FILTER_VALIDATE_URL) ? $path : realpath($path);

		$relativePath = trim(str_replace(array(realpath($this->publicPath), '\\'), array('', '/'), $absolutePath), '/');

		return new Asset($this->files, $absolutePath, $relativePath, $this->environment);
	}

	/**
	 * Determine if an asset exists relative from the public directory.
	 * 
	 * @param  string  $path
	 * @return bool
	 */
	public function find($path)
	{
		return $this->files->exists($this->publicPath.'/'.$path);
	}

	/**
	 * Get the absolute path to an asset relative to the public directory.
	 * 
	 * @param  string  $path
	 * @return string
	 */
	public function path($path)
	{
		return $this->publicPath.'/'.$path;
	}

}