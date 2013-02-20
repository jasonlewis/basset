<?php namespace JasonLewis\Basset;

use FilesystemIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Illuminate\Filesystem\Filesystem;

class Directory implements FilterableInterface {

	/**
	 * Path to the directory.
	 * 
	 * @var string
	 */
	protected $path;

	/**
	 * Illuminate filesystem instance.
	 * 
	 * @var Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Basset asset manager instance.
	 * 
	 * @var JasonLewis\Basset\AssetManager
	 */
	protected $manager;

	/**
	 * Array of assets.
	 * 
	 * @var array
	 */
	protected $assets;

	/**
	 * Array of filters.
	 * 
	 * @var array
	 */
	protected $filters;

	/**
	 * Create a new directory instance.
	 * 
	 * @param  Illuminate\Filesystem\Filesystem  $files
	 * @param  JasonLewis\Basset\AssetManager  $manager
	 * @param  string  $path
	 * @return void
	 */
	public function __construct(Filesystem $files, AssetManager $manager, $path)
	{
		$this->files = $files;
		$this->manager = $manager;
		$this->path = $path;
	}

	/**
	 * Recursively iterate through the directory.
	 * 
	 * @return RecursiveIteratorIterator
	 */
	public function recursivelyIterateDirectory()
	{
		return new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->path));
	}

	/**
	 * Iterate through the directory.
	 * 
	 * @return FilesystemIterator
	 */
	public function iterateDirectory()
	{
		return new FilesystemIterator($this->path);	
	}

	/**
	 * Require the current directory.
	 * 
	 * @return JasonLewis\Basset\Directory
	 */
	public function requireDirectory()
	{
		foreach ($this->iterateDirectory() as $file)
		{
			if ($file->isFile())
			{
				$this->assets[] = $this->manager->make($file->getPathname());
			}
		}

		return $this;
	}

	/**
	 * Require the current directory tree.
	 * 
	 * @return JasonLewis\Basset\Directory
	 */
	public function requireTree()
	{
		foreach ($this->recursivelyIterateDirectory() as $file)
		{
			if ($file->isFile())
			{
				$this->assets[] = $this->manager->make($file->getPathname());
			}
		}

		return $this;
	}

	/**
	 * Exclude an array of assets.
	 * 
	 * @param  array  $assets
	 * @return JasonLewis\Basset\Directory
	 */
	public function except($assets)
	{
		foreach ($this->assets as $key => $asset)
		{
			if (in_array($asset->getRelativePath(), (array) $assets))
			{
				unset($this->assets[$key]);
			}
		}

		return $this;
	}

	/**
	 * Include only a subset of assets.
	 * 
	 * @param  array  $assets
	 * @return JasonLewis\Basset\Directory
	 */
	public function only($assets)
	{
		foreach ($this->assets as $key => $asset)
		{
			if ( ! in_array($asset->getRelativePath(), (array) $assets))
			{
				unset($this->assets[$key]);
			}
		}

		return $this;
	}

	/**
	 * Get the path to the directory.
	 * 
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Get the array of assets.
	 * 
	 * @return array
	 */
	public function getAssets()
	{
		return $this->assets;
	}

	/**
	 * Process the directory assets by applying any filters on the directory to each asset.
	 * 
	 * @return array
	 */
	public function processAssets()
	{
		$assets = array();

		foreach ($this->assets as $asset)
		{
			foreach ($this->filters as $filter)
			{
				$asset->apply($filter);
			}

			$assets[] = $asset;
		}

		// After applying all the filters to all the assets we'll reset the filters array.
		$this->filters = array();

		return $assets;
	}

	/**
	 * Apply a filter to an entire directory.
	 * 
	 * @param  string  $filter
	 * @param  Closure  $callback
	 * @return JasonLewis\Basset\Filter
	 */
	public function apply($filter, Closure $callback = null)
	{
		$filter = new Filter($filter, $this);

		if (is_callable($callback))
		{
			call_user_func($callback, $filter);
		}

		return $this->filters[$filter->getFilter()] = $filter;
	}

}