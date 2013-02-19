<?php namespace JasonLewis\Basset;

use Closure;
use RuntimeException;
use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;

class Collection implements FilterableInterface {

	/**
	 * Name of the collection.
	 * 
	 * @var string
	 */
	protected $name;

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
	protected $assets = array();

	/**
	 * Array of filters.
	 * 
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Collection working directory.
	 * 
	 * @var JasonLewis\Basset\Directory
	 */
	protected $workingDirectory;

	/**
	 * Create a new collection instance.
	 * 
	 * @param  string  $name
	 * @param  Illuminate\Filesystem\Filesystem  $files
	 * @param  Illuminate\Config\Repository  $config
	 * @param  JasonLewis\Basset\AssetManager  $manager
	 * @return void
	 */
	public function __construct($name, Filesystem $files, Repository $config, AssetManager $manager)
	{
		$this->name = $name;
		$this->files = $files;
		$this->config = $config;
		$this->manager = $manager;
	}

	/**
	 * Get the name of the collection.
	 * 
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Add an asset to the collection.
	 * 
	 * @param  string  $name
	 * @return JasonLewis\Basset\Asset
	 */
	public function add($name)
	{
		$assetPath = null;

		// Determine if the asset has been given an alias. We'll use the alias as the name of
		// the asset.
		if ($this->config->has("basset::assets.{$name}"))
		{
			$name = $this->config->get("basset::assets.{$name}");
		}

		// Determine if the asset is a remotely hosted asset. We can check that by detecting if
		// the name contains a URL scheme.
		if (parse_url($name, PHP_URL_SCHEME))
		{
			$assetPath = $name;
		}

		// If the name of the asset is prefixed with 'path: ' then the absolute path to the asset
		// is being provided. This is best avoided as assets should always be within the public
		// directory.
		elseif (starts_with($name, 'path: '))
		{
			$assetPath = substr($name, 6);
		}

		// Determine if the asset exists within the current working directory.
		elseif ($this->workingDirectory instanceof Directory and $this->files->exists($this->workingDirectory->getPath().'/'.$name))
		{
			$assetPath = $this->workingDirectory->getPath().'/'.$name;
		}

		// Determine if the asset exists within the public directory.
		elseif ($this->manager->find($name))
		{
			$assetPath = $this->manager->path($name);
		}

		// Lastly we'll attempt to locate the asset by spinning through all of the named directories.
		// If the asset cannot be found then we'll make no attempt to continue further.
		else
		{
			foreach ($this->config->get('basset::directories') as $directoryName => $directoryPath)
			{
				$directory = $this->parseDirectoryPath($directoryPath);

				if ( ! $directory instanceof Directory)
				{
					continue;
				}

				foreach ($directory->recursivelyIterateDirectory() as $file)
				{
					$filePath = $file->getRealPath();

					if (ends_with($this->normalizePath($filePath), $name))
					{
						$assetPath = $filePath;

						break 2;
					}
				}
			}
		}

		if ( ! is_null($assetPath) and $this->files->exists($assetPath))
		{
			$asset = $this->manager->make($assetPath);

			return $this->assets[] = $asset;
		}
	}

	/**
	 * Change the working directory.
	 * 
	 * @param  string  $path
	 * @param  Closure  $callback
	 * @return Basset\Collection
	 */
	public function directory($path, Closure $callback)
	{
		$this->workingDirectory = $this->parseDirectoryPath($path);

		// Once we've set the working directory we'll fire the callback so that any added assets
		// are relative to the working directory. After the callback we can revert the working
		// directory.
		call_user_func($callback, $this);

		$this->workingDirectory = null;

		return $this;
	}

	public function requireDirectory($path = null)
	{
		// If no path was given then we'll check if we're working within a directory. If not then the
		// method is not being used correctly.
		if (is_null($path))
		{
			if ($this->workingDirectory instanceof Directory)
			{
				$directory = $this->workingDirectory;
			}
			else
			{
				throw new RuntimeException('Basset is not within a working directory, please supply a path to Basset\Collection::requireDirectory().');
			}
		}
		else
		{
			$directory = $this->parseDirectoryPath($path);
		}

		return $this->assets[] = $directory->requireDirectory();
	}

	/**
	 * Parse a directory path and return a directory instance.
	 * 
	 * @param  string  $directory
	 * @return JasonLewis\Basset\Directory
	 */
	public function parseDirectoryPath($path)
	{
		// Determine if the directory has been given an alias. We'll use the alias as the path to
		// the directory.
		if (starts_with($path, 'name: '))
		{
			$name = substr($path, 6);

			if ($this->config->has("basset::directories.{$name}"))
			{
				$path = $this->config->get("basset::directories.{$name}");
			}
		}

		// If the path to the directory is prefixed with 'path: ' then the absolute path to the
		// directory is being provided.
		if (starts_with($path, 'path: '))
		{
			$path = substr($path, 6);
		}

		// Lastly we'll prefix the directory path with the path to the public directory.
		else
		{
			$path = $this->manager->path($path);
		}

		if ($this->files->exists($path))
		{
			return new Directory($path, $this->files, $this->manager);
		}
	}

	/**
	 * Normalize a give path.
	 * 
	 * @param  string  $path
	 * @return string
	 */
	protected function normalizePath($path)
	{
		return str_replace('\\', '/', $path);
	}

	/**
	 * Apply a filter to the entire collection.
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

		return $this->filters[] = $filter;
	}

}