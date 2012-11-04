<?php namespace Basset;

use SplFileInfo;
use ReflectionClass;
use Illuminate\Filesystem;
use Assetic\Asset\FileAsset;
use Illuminate\Config\Repository;

class Asset {

	/**
	 * Filename of asset.
	 * 
	 * @var string
	 */
	protected $name;

	/**
	 * Pathname of asset.
	 * 
	 * @var string
	 */
	protected $path;

	/**
	 * Extension of asset.
	 * 
	 * @var string
	 */
	protected $extension;

	/**
	 * Last modified timestamp of asset.
	 * 
	 * @var int
	 */
	protected $modified;

	/**
	 * Base directory of asset.
	 * 
	 * @var string
	 */
	protected $directory;

	/**
	 * Filesystem instance.
	 * 
	 * @var Illuminate\Filesystem
	 */
	protected $files;

	/**
	 * Config repository instance.
	 * 
	 * @var Illuminate\Config\Repository
	 */
	protected $config;

	/**
	 * Array of extension groups.
	 * 
	 * @var array
	 */
	protected $groups = array(
		'js'	 => 'script',
		'coffee' => 'script',
		'css'	 => 'style',
		'less'	 => 'style',
		'sass'	 => 'style',
		'scss'	 => 'style'
	);

	/**
	 * Array of filters to apply to the asset.
	 * 
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Create a new asset instance.
	 * 
	 * @param  SplFileInfo  $asset
	 * @param  string  $directory
	 * @param  Illuminate\Filesystem  $files
	 * @param  Illuminate\Config\Repository  $config
	 * @return void
	 */
	public function __construct(SplFileInfo $asset, $directory, Filesystem $files, Repository $config)
	{
		$this->directory = realpath($directory);
		$this->files = $files;
		$this->config = $config;

		// Using the SplFileInfo object we can set some required information about the asset.
		$this->name = $asset->getFilename();
		$this->path = realpath($asset->getPathname());
		$this->extension = $asset->getExtension();
		$this->modified = $asset->getMTime();
	}

	/**
	 * Get the filename of the asset.
	 * 
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Get the pathname of the asset.
	 * 
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Get the extension of the asset.
	 * 
	 * @return string
	 */
	public function getExtension()
	{
		return $this->extension;
	}

	/**
	 * Get the modified timestamp of the asset.
	 * 
	 * @return int
	 */
	public function getModified()
	{
		return $this->modified;
	}

	/**
	 * Get the relative path of the asset.
	 * 
	 * @return string
	 */
	public function getRelativePath()
	{
		return trim(str_replace(array($this->directory, '\\'), array('', '/'), $this->path), '/');
	}

	/**
	 * Get the group of asset.
	 * 
	 * @return string
	 */
	public function getGroup()
	{
		return $this->groups[$this->extension];
	}

	/**
	 * Get the assets filters.
	 * 
	 * @return array
	 */
	public function getFilters()
	{
		return $this->filters;
	}

	/**
	 * Determine if an asset is valid.
	 * 
	 * @return bool
	 */
	public function isValid()
	{
		return isset($this->groups[$this->extension]);
	}

	/**
	 * Return the raw HTML.
	 * 
	 * @return string
	 */
	public function rawHtml()
	{
		return new Html($this->getGroup(), $this->getExtension(), path($this->config['basset.handles'].'/'.$this->getRelativePath()));
	}

	/**
	 * Compile the asset.
	 * 
	 * @return string
	 */
	public function compile()
	{
		// Before we compile the asset the registered filters need to be applied. Filters
		// are applied with the help of Assetic's FileAsset class.
		$filters = array();

		foreach ($this->filters as $filter => $arguments)
		{
			if (class_exists($filter = "Assetic\\Filter\\{$filter}"))
			{
				$reflection = new ReflectionClass($filter);

				$filters[] = $reflection->newInstanceArgs((array) $arguments);
			}
		}

		$asset = new FileAsset($this->getPath(), $filters);

		return $asset->dump();
	}

	/**
	 * Apply a filter to the collection.
	 * 
	 * @param  string  $filter
	 * @param  array  $options
	 * @return Basset\Filter
	 */
	public function apply($filter, $options = array())
	{
		if (isset($this->config["basset.filters.{$filter}"]))
		{
			$filter = $this->config["basset.filters.{$filter}"];

			if (is_array($filter))
			{
				list($options, $filter) = array(current($filter), key($filter));
			}
			else
			{
				$options = array();
			}
		}

		$this->filters[$filter] = $options;

		return $this;
	}

}