<?php namespace JasonLewis\Basset;

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
	 * Path to public directory.
	 * 
	 * @var string
	 */
	protected $publicPath;

	/**
	 * Create a new collection instance.
	 * 
	 * @param  string  $name
	 * @return void
	 */
	public function __construct($name, Filesystem $files, Repository $config, $publicPath)
	{
		$this->name = $name;
		$this->files = $files;
		$this->config = $config;
		$this->publicPath = $publicPath;
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

		// Determine if the asset exists within the public directory.
		elseif ($this->files->exists($this->publicPath.'/'.$name))
		{
			$assetPath = $this->publicPath.'/'.$name;
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
			$asset = new Asset(realpath($assetPath));

			return $asset;
		}
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
			$path = $this->publicPath.'/'.$path;
		}

		if ($this->files->exists($path))
		{
			return new Directory($path);
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
	 * @return JasonLewis\Basset\Filter
	 */
	public function apply($filter)
	{

	}

}