<?php namespace Basset;

use FilesystemIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class Directory {

	/**
	 * Path to the directory.
	 * 
	 * @var string
	 */
	protected $path;

	/**
	 * Illuminate application instance.
	 * 
	 * @var Illuminate\Foundation\Application  $app
	 */
	protected $app;

	/**
	 * Pending array of assets.
	 * 
	 * @var array
	 */
	protected $pending;

	/**
	 * Create a new directory instance.
	 * 
	 * @param  string  $path
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function __construct($path, $app)
	{
		$this->path = $path;
		$this->app = $app;
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
	 * Recursively require the current directory tree.
	 * 
	 * @return Basset\Directory
	 */
	public function requireTree()
	{
		// Spin through all the files within the directory and add them to the pending array of assets.
		// This allows assets to be excluded or included before being added as valid.
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->getPath())) as $file)
		{
			if ($file->isDir()) continue;

			$asset = new Asset($file, $this->getPath(), $this->app);

			if ($asset->isValid())
			{
				$this->pending[] = $asset;
			}
		}

		return $this;
	}

	/**
	 * Require the current directory.
	 * 
	 * @return Basset\Directory
	 */
	public function requireDirectory()
	{
		// Spin through all the files within the directory and add them to the pending array of assets.
		// This allows assets to be excluded or included before being added as valid.
		foreach (new FilesystemIterator($this->getPath()) as $file)
		{
			$asset = new Asset($file, $this->getPath(), $this->app);

			if ($asset->isValid())
			{
				$this->pending[] = $asset;
			}
		}

		return $this;
	}

	/**
	 * Get the pending assets.
	 * 
	 * @return array
	 */
	public function getPending()
	{
		return $this->pending;
	}

	/**
	 * Apply a filter to the directories assets.
	 * 
	 * @param  string  $filter
	 * @param  array   $options
	 * @return Basset\Directory
	 */
	public function apply($filter, $options = array())
	{
		foreach ($this->pending as $asset)
		{
			$asset->apply($filter, $options);
		}

		return $this;
	}

	/**
	 * Exclude an array of assets.
	 * 
	 * @param  array  $assets
	 * @return Basset\Filter
	 */
	public function except($assets)
	{
		if ( ! is_array($assets)) $assets = array($assets);

		foreach ($this->pending as $key => $asset)
		{
			if (in_array($asset->getRelativePath(), $assets))
			{
				unset($this->pending[$key]);
			}
		}

		return $this;
	}

	/**
	 * Include assets only in the array.
	 * 
	 * @param  array  $assets
	 * @return Basset\Filter
	 */
	public function only($assets)
	{
		if ( ! is_array($assets)) $assets = array($assets);

		foreach ($this->pending as $key => $asset)
		{
			if ( ! in_array($asset->getRelativePath(), $assets))
			{
				unset($this->pending[$key]);
			}
		}

		return $this;
	}

}