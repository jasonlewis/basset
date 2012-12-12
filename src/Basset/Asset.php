<?php namespace Basset;

use ReflectionClass;
use Assetic\Asset\StringAsset;

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
	 * Asset is remotely hosted.
	 * 
	 * @var bool
	 */
	protected $remote;

	/**
	 * Base directory of asset.
	 * 
	 * @var string
	 */
	protected $directory;

	/**
	 * Illuminate application instance.
	 * 
	 * @var Illuminate\Foundation\Application
	 */
	protected $app;

	/**
	 * Asset files contents.
	 * 
	 * @var string
	 */
	protected $contents;

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
	 * @param  string  $path
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function __construct($path, $app)
	{
		$this->app = $app;
		$this->path = $path;
		$this->name = basename($path);
		$this->contents = $app['files']->getRemote($path);
		$this->extension = $app['files']->extension($path);
		$this->remote = parse_url($path, PHP_URL_SCHEME);

		if ( ! $this->remote)
		{
			$this->modified = $app['files']->lastModified($path);
		}
	}

	/**
	 * Get the contents of the asset.
	 * 
	 * @return string
	 */
	public function getContents()
	{
		return $this->contents;
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
	public function getLastModified()
	{
		return $this->modified;
	}

	/**
	 * Get the relative path of the asset to the public directory.
	 * 
	 * @return string
	 */
	public function getRelativePath()
	{
		return trim(str_replace(array($this->app['path.public'], '\\'), array('', '/'), $this->path), '/');
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
	 * Determine if asset is remotely hosted.
	 * 
	 * @return string
	 */
	public function isRemote()
	{
		return $this->remote;
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
	 * Compile the asset.
	 * 
	 * @return string
	 */
	public function compile()
	{
		$filters = array();
		
		foreach ($this->filters as $name => $arguments)
		{
			if (class_exists($filter = "Assetic\\Filter\\{$name}") or class_exists($filter = "Basset\\Filter\\{$name}"))
			{
				$reflection = new ReflectionClass($filter);

				$filters[] = $reflection->newInstanceArgs((array) $arguments);
			}
		}

		$asset = new StringAsset($this->contents, $filters, dirname($this->path), $this->name);

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
		if ($this->app['config']->has("basset::filters.{$filter}"))
		{
			$filter = $this->app['config']->get("basset::filters.{$filter}");

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