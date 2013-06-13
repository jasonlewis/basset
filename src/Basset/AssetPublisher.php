<?php namespace Basset;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;

class AssetPublisher {

	/**
	 * Illuminate filesystem instance.
	 * 
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Illuminate config repository instance.
	 * 
	 * @var \Illuminate\Config\Repository
	 */
	protected $config;

	/**
	 * Path to the public directory.
	 * 
	 * @var string
	 */
	protected $publicPath;

	/**
	 * Path to the base directory.
	 * 
	 * @var string
	 */
	protected $basePath;

	/**
	 * Path to the publish directory.
	 * 
	 * @var string
	 */
	protected $publishPath;

	/**
	 * An array of published paths.
	 * 
	 * @var array
	 */
	protected $published = array();

	/**
	 * Create a new asset publisher instance.
	 * 
	 * @param  \Illuminate\Filesytem\Filesystem  $files
	 * @param  \Illuminate\Config\Repository  $config
	 * @param  string  $publicPath
	 * @param  string  $basePath
	 * @param  string  $publishPath
	 * @return void
	 */
	public function __construct(Filesystem $files, Repository $config, $publicPath, $basePath, $publishPath)
	{
		$this->files = $files;
		$this->config = $config;
		$this->publicPath = $publicPath;
		$this->basePath = $basePath;
		$this->publishPath = $publishPath;
	}

	/**
	 * Publish an array of paths to the public directory.
	 * 
	 * @param  array  $paths
	 * @return void
	 */
	public function publish(array $paths)
	{
		$this->published = array();

		foreach ($paths as $path)
		{
			$path = resolve_path($path);

			// If the path is seen to be within the "vendor" directory then we'll publish
			// the path as such. This results in a cleaner directory structure and is
			// great for things like Bootstrap.
			if ($this->withinVendor($path))
			{
				$this->publishVendorPath($path);
			}

			// If not then we'll just do a simple copy. This may result in an uglier
			// directory structure but it gets the job done.
			else
			{
				$publishPath = $this->publishPath.'/'.$this->removeBasePath($path);

				$this->attempt($path, $publishPath);
			}
		}

		return $this->published;
	}

	/**
	 * Publish a "vendor/package" formatted path. Think Bootstrap.
	 * 
	 * @param  string  $path
	 * @return void
	 */
	protected function publishVendorPath($path)
	{
		$relativePath = substr($this->removeBasePath($path), 7);

		// Split the relative path into segments. We'll then splice the first two segments
		// off as they are the "vendor/package" and then the remaining segments will
		// become the relative path.
		$segments = explode('/', $relativePath);

		list($vendor, $package) = array_splice($segments, 0, 2);

		$relativePath = implode('/', $segments);

		// Build the path to the published path so we can copy the original directory
		// over.
		$publishPath = $this->publishPath.'/'.$vendor.'/'.$package.'/'.$relativePath;

		$this->attempt($path, $publishPath);
	}

	/**
	 * Attempt to publish a path to the publish path.
	 * 
	 * @param  string  $originalPath
	 * @param  string  $publishPath
	 * @return void
	 */
	protected function attempt($originalPath, $publishPath)
	{
		if ($this->files->isDirectory($originalPath))
		{
			if ($this->files->exists($publishPath))
			{
				// If the directory already exists we'll make sure there were some changes
				// to the directory. If there are no changes there is no point publishing
				// the assets again. If there were changes we'll delete the directory
				// and do a clean publish.
				if ( ! $this->directoryHasChanged($originalPath, $publishPath))
				{
					return;
				}
				else
				{
					$this->files->deleteDirectory($publishPath);
				}
			}

			$this->files->copyDirectory($originalPath, $publishPath);
		}
		else
		{
			$basePath = dirname($publishPath);

			if ( ! $this->files->exists($basePath))
			{
				$this->files->makeDirectory($basePath, 0777, true);
			}
			elseif ($this->files->exists($publishPath))
			{
				// If the file already exists then we'll compare the contents of each file
				// to see if the asset needs to be republished. If it was republished
				// every time then it would trigger a rebuild each and every time.
				// That isn't exactly ideal.
				if ($this->files->get($publishPath) == $this->files->get($originalPath))
				{
					return;
				}
			}

			$this->files->copy($originalPath, $publishPath);
		}

		$this->published($originalPath, $publishPath);
	}

	/**
	 * Determines if a directory or files within the directory have changed.
	 * 
	 * @param  string  $originalPath
	 * @param  string  $publishPath
	 * @return bool
	 */
	protected function directoryHasChanged($originalPath, $publishPath)
	{
		$original = $this->directoryStructureArray($originalPath);

		$published = $this->directoryStructureArray($publishPath);

		return $original != $published;	
	}

	/**
	 * Create an array out of the directory structure.
	 * 
	 * @param  string  $path
	 * @return array
	 */
	protected function directoryStructureArray($path)
	{
		$structure = array();

		// Recursively spin through all of the files and directories within the path we're
		// scanning and add them to a structure array. This allows us to compare two
		// directories to see if there were any changes that would require the
		// assets to be republished.
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file)
		{
			$key = str_replace($path, '', $file->getPathname());

			if ($file->isFile())
			{
				$structure[$key] = $this->files->get($file->getPathname());
			}
			elseif ($file->isDir())
			{
				$structure[] = $key;
			}
		}

		return $structure;
	}

	/**
	 * Add a published path to the array.
	 * 
	 * @param  string  $originalPath
	 * @param  string  $publishPath
	 * @return void
	 */
	protected function published($originalPath, $publishPath)
	{
		$this->published[$this->removeBasePath($originalPath)] = $this->removeBasePath($publishPath);
	}

	/**
	 * Determine if a path is inside the vendor directory.
	 * 
	 * @param  string  $path
	 * @return bool
	 */
	protected function withinVendor($path)
	{
		$path = $this->removeBasePath($path);
		
		return starts_with($path, 'vendor');
	}

	/**
	 * Strip the base path from a given path.
	 * 
	 * @param  string  $path
	 * @return string
	 */
	protected function removeBasePath($path)
	{
		$basePath = preg_quote(resolve_path($this->basePath), '/');

		$path = preg_replace('/^'.$basePath.'/', '', str_replace('\\', '/', $path));

		return $this->cleanPath($path);
	}

	/**
	 * Clean a given path.
	 * 
	 * @param  string  $path
	 * @return string
	 */
	protected function cleanPath($path)
	{
		return preg_replace("/[\/\\\]+/", '/', trim($path, '/'));
	}

}