<?php namespace Basset;

use Closure;
use RuntimeException;
use FilesystemIterator;
use Illuminate\Filesystem;
use InvalidArgumentException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Illuminate\Config\Repository;

class Collection {

	/**
	 * Name of asset collection.
	 * 
	 * @var string
	 */
	protected $name;

	/**
	 * Config repository instance.
	 * 
	 * @var Illuminate\Config\Repository
	 */
	protected $config;

	/**
	 * Filesystem instance.
	 * 
	 * @var Illuminate\Filesystem
	 */
	protected $files;

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
	 * Create a new Basset instance.
	 * 
	 * @param  string  $name
	 * @param  Illuminate\Filesystem  $files
	 * @param  Illuminate\Config\Respository  $config
	 * @return void
	 */
	public function __construct($name, Filesystem $files, Repository $config)
	{
		$this->name = $name;
		$this->config = $config;
		$this->files = $files;
	}

	/**
	 * Cascade through the directories and add the asset if it can be found.
	 * 
	 * @param  string  $name
	 * @return Basset\Asset
	 */
	public function add($name)
	{
		foreach ($this->config['basset.directories'] as $directory => $path)
		{
			$directory = $this->parseDirectory($path);

			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory->getPath())) as $file)
			{
				// Once we find the first occurance of the file we'll add it to the array of assets if it
				// does not already exist. This is called cascading file loading.
				$filename = trim(str_replace(array(realpath($directory->getPath()), '\\'), array('', '/'), $file->getRealPath()), '/');

				if ($filename == $name)
				{
					$asset = new Asset($file, $directory->getPath(), $this->files, $this->config);

					if ($asset->isValid() and ! in_array($asset, $this->assets) or ! in_array($asset, $this->pending))
					{
						return $this->pending[] = $asset;
					}
				}
			}
		}
	}

	/**
	 * Work within a directory.
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
			if($asset->getModified() > $lastModified)
			{
				$lastModified = $asset->getModified();
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
		return $this->files->exists($this->config['path.base'].'/'.$this->config['basset.compiling_path'].'/'.$this->getCompiledName($group));
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
			$names[] = $asset->getRelativePath();
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
		if (starts_with($directory, 'path: '))
		{
			$directory = substr($directory, 6);
		}
		else
		{
			if ( isset($this->config["basset.directories.{$directory}"]))
			{
				$directory = $this->config["basset.directories.{$directory}"];
			}

			if (starts_with($directory, 'path: '))
			{
				$directory = substr($directory, 6);
			}
			else
			{
				$directory = $this->config['path.base'].'/'.$directory;
			}
		}

		return new Directory($directory, $this->files, $this->config);
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

}