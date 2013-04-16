<?php namespace Basset;

use Closure;
use RuntimeException;

class Collection {

	/**
	 * Name of asset collection.
	 * 
	 * @var string
	 */
	protected $name;

	/**
	 * Illuminate application instance.
	 * 
	 * @var Illuminate\Foundation\Application  $app
	 */
	protected $app;

	/**
	 * Collection directory.
	 * 
	 * @var string
	 */
	protected $directory;

	/**
	 * Array of assets.
	 * 
	 * @var array
	 */
	protected $assets = array();

	/**
	 * Array of pending assets.
	 * 
	 * @var array
	 */
	protected $pending = array();

	/**
	 * Array of excluded assets.
	 * 
	 * @var array
	 */
	protected $exclude = array();

	/**
	 * Array of filters.
	 * 
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Create a new Basset\Collection instance.
	 * 
	 * @param  string  $name
	 * @param  Illuminate\Foundation\Application  $app
	 * @param  string  $publicPath
	 * @return void
	 */
	public function __construct($name, $app)
	{
		$this->name = $name;
		$this->app = $app;
	}

	/**
	 * Cascade through the directories and add the asset if it can be found.
	 * 
	 * @param  string  $name
	 * @return Basset\Asset
	 */
	public function add($name)
	{
		$assetPath = null;

		// Check to see if the asset has been aliased, if it has then we'll use its value as the
		// asset name as aliased assets may make use of remote assets or path prefixed assets.
		if ($this->app['config']->has("basset::assets.{$name}"))
		{
			$name = $this->app['config']->get("basset::assets.{$name}");
		}

		// Add the path when accessing from directory() method closure.
		if ($this->directory instanceof Directory)
		{
			$assetPath = $this->directory->getPath().'/'.$name;
		}
		// Add an asset by using the full path to its location.
		elseif (starts_with($name, 'path: '))
		{
			$assetPath = substr($name, 6);
		}

		// Check if the asset is a remotely hosted asset such as jQuery.
		elseif (parse_url($name, PHP_URL_SCHEME))
		{
			$assetPath = $name;
		}

		// Add an asset that's located within the public directory.
		elseif ($this->app['files']->exists($this->app['path.public'].'/'.$name))
		{
			$assetPath = $this->app['path.public'].'/'.$name;	
		}

		// Add an asset that's within one of the configured directories.
		else
		{
			foreach ($this->app['config']->get('basset::directories') as $directory => $path)
			{
				$directory = $this->parseDirectory($path);

				foreach ($directory->recursivelyIterateDirectory() as $file)
				{
					// Once we find the first occurance of the file we'll add it to the array of assets if it
					// does not already exist. This is called cascading file loading.
					$directoryPath = $directory->getPath();

					if (realpath($directoryPath))
					{
						$directoryPath = realpath($directoryPath);
					}

					$filename = trim(str_replace(array($directoryPath, '\\'), array('', '/'), $file->getRealPath()), '/');

					if ($filename == $name)
					{
						$assetPath = $file->getPathname();

						break 2;
					}
				}
			}
		}
		
		// Create a new Asset instance and validate it, if it's not already in the array of assets or pending assets
		// we can go ahead and add it.
		$asset = new Asset($assetPath, $this->app);

		if (( ! in_array($asset, $this->assets) or ! in_array($asset, $this->pending)) and $asset->isValid())
		{
			return $this->pending[] = $asset;
		}

		throw new RuntimeException("Could not find asset [{$name}]");
	}

	/**
	 * Change the working directory.
	 * 
	 * @param  string  $directory
	 * @param  Closure  $callback
	 * @return Basset\Collection
	 */
	public function directory($directory, Closure $callback)
	{
		$this->directory = $this->parseDirectory($directory);

		// Once the directory has been set we can fire the callback with our current collection so we can begin
		// to work within the directory specified. After the callback we'll revert the current working directory
		// to null so that the standard cascading structure is used.
		$callback($this);

		$this->directory = null;

		return $this;
	}

	/**
	 * Require a directory or use the current working directory.
	 * 
	 * @param  string  $directory
	 * @return Basset\Directory
	 */
	public function requireDirectory($directory = null)
	{
		if (is_null($directory))
		{
			if (is_null($this->directory))
			{
				throw new RuntimeException('Basset is not working within a directory and no directory was supplied to Basset\Collection::requireDirectory()');
			}

			$directory = $this->directory;
		}
		else
		{
			$directory = $this->parseDirectory($directory);
		}

		return $this->pending[] = $directory->requireDirectory();
	}

	/**
	 * Recursively require a directory tree.
	 * 
	 * @param  string  $directory
	 * @return Basset\Directory
	 */
	public function requireTree($directory = null)
	{
		if (is_null($directory))
		{
			if (is_null($this->directory))
			{
				throw new RuntimeException('Basset is not working within a directory and no directory was supplied to Basset\Collection::requireDirectory()');
			}

			$directory = $this->directory;
		}
		else
		{
			$directory = $this->parseDirectory($directory);
		}

		return $this->pending[] = $directory->requireTree();
	}

	/**
	 * Get the last modified assets timestamp.
	 * 
	 * @param  string  $group
	 * @return int
	 */
	public function lastModified($group)
	{
		$lastModified = 0;

		foreach ($this->assets[$group] as $asset)
		{
			if($asset->getLastModified() > $lastModified)
			{
				$lastModified = $asset->getLastModified();
			}
		}

		return $lastModified;
	}

	/**
	 * Get the valid assets.
	 * 
	 * @return array
	 */
	public function getAssets($group = null)
	{
		foreach ($this->pending as $pending)
		{
			// If the pending object is an instance of Basset\Directory then we need to get
			// the pending assets of the directory and add them.
			if ($pending instanceof Directory)
			{
				foreach ($pending->getPending() as $asset)
				{
					$this->assets[$asset->getGroup()][] = $asset;
				}
			}
			else
			{
				$this->assets[$pending->getGroup()][] = $pending;
			}
		}

		$this->pending = array();

		if (is_null($group))
		{
			return $this->assets;
		}
		else
		{
			return isset($this->assets[$group]) ? $this->assets[$group] : array();
		}
	}

	/**
	 * Determine if the group on the collection has been compiled.
	 * 
	 * @param  string  $group
	 * @return bool
	 */
	public function isCompiled($group)
	{
		return $this->app['files']->exists($this->getCompilingPath().'/'.$this->getCompiledName($group));
	}

	/**
	 * Get the MD5 fingerprint of the collection.
	 * 
	 * @param  string  $group
	 * @return string
	 */
	public function getFingerprint($group)
	{
		$names = array();

		foreach ($this->getAssets($group) as $asset)
		{
			$names[] = $asset->getLastModified();

			foreach ($asset->getFilters() as $filter => $options)
			{
				$names[] = $filter;
			}
		}

		return md5(implode(PHP_EOL, $names));
	}

	/**
	 * Get the compiled name of the collection.
	 * 
	 * @param  string  $group
	 * @return string
	 */
	public function getCompiledName($group)
	{
		$extension = ($group == 'style' ? 'css' : 'js');

		return "{$this->name}-{$this->getFingerprint($group)}.{$extension}";
	}

	/**
	 * Get the path to the compiling directory.
	 * 
	 * @return string
	 */
	public function getCompilingPath()
	{
		return $this->app['path.base'].'/'.$this->app['config']->get('basset::public').'/'.$this->app['config']->get('basset::compiling_path');
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
	 * Parse a directory and return the path to the directory.
	 * 
	 * @param  string  $directory
	 * @return string
	 */
	public function parseDirectory($directory)
	{
		// Directories can be given as a full path to the directory.
		if (starts_with($directory, 'path: '))
		{
			$directory = substr($directory, 6);
		}

		// Directories can be given names and then easily aliased with these names.
		elseif (starts_with($directory, 'name: '))
		{
			$directory = substr($directory, 6);

			if ($this->app['config']->has("basset::directories.{$directory}"))
			{
				$directory = $this->app['config']->get("basset::directories.{$directory}");
			}

			// Named directories may also provide full paths to the directory.
			if (starts_with($directory, 'path: '))
			{
				$directory = substr($directory, 6);
			}
			else
			{
				$directory = $this->app['path.base'].'/'.$directory;
			}
		}

		// Default to looking for the directory within the public directory.
		else
		{
			$directory = $this->app['path.public'].'/'.$directory;
		}

		return new Directory($directory, $this->app);
	}

	/**
	 * Compile the group on the collection.
	 * 
	 * @param  string  $group
	 * @return string
	 */
	public function compile($group)
	{
		$assets = $this->getAssets($group);

		$return = array();

		foreach ($assets as $asset)
		{
			$return[] = $asset->compile();
		}

		return implode(PHP_EOL, $return);
	}

	/**
	 * Apply a filter to an entire collection.
	 * 
	 * @param  string  $filter
	 * @param  array  $options
	 * @return Basset\Collection
	 */
	public function apply($filter, $options = array())
	{
		foreach ($this->getAssets('style') as $key => $asset)
		{
			$this->assets['style'][$key]->apply($filter, $options);
		}

		foreach ($this->getAssets('script') as $key => $asset)
		{
			$this->assets['script'][$key]->apply($filter, $options);
		}

		return $this;
	}

}