<?php namespace Basset;

use Str;
use File;
use HTML;
use Event;
use Bundle;

class Container {

	/**
	 * Array of shared assets.
	 * 
	 * @var array
	 */
	public static $shared = array();

	/**
	 * The route the assets will be display on.
	 * 
	 * @var string
	 */
	protected $route;

	/**
	 * The group the assets belong to, either scripts or styles.
	 * 
	 * @var string
	 */
	protected $group;

	/**
	 * The cache object used to store the cached assets.
	 * 
	 * @var object
	 */
	protected $cache;

	/**
	 * The array containing all the registered assets.
	 * 
	 * @var array
	 */
	protected $assets = array(
		'styles'  => array(),
		'scripts' => array()
	);

	/**
	 * The current directory to register assets from.
	 * 
	 * @var string
	 */
	protected $directory;

	/**
	 * The array of registered symlinks.
	 * 
	 * @var array
	 */
	protected $symlinks = array();

	/**
	 * The array of configuration settings.
	 * 
	 * @var array
	 */
	public $config = array();

	/**
	 * Create a new Basset\Container instance.
	 *
	 * @param  string  $route
	 * @param  string  $group
	 * @return void
	 */
	public function __construct($route, $group = null)
	{
		$this->route = $route;

		$this->group = $group;

		$this->cache = new Cache($route);

		$this->config = new Config;
	}

	/**
	 * Create a new directory collection of assets.
	 *
	 * @param  string   $directory
	 * @param  Closure  $callback
	 * @return object
	 */
	public function directory($directory, $callback)
	{
		if(strpos($directory, '::') !== false)
		{
			list($bundle, $directory) = explode('::', $directory);

			$directory = 'path: ' . path('public') . trim(Bundle::assets($bundle), '/') . $directory;
		}

		if(starts_with($directory, 'path: '))
		{
			$directory = substr($directory, 6);
		}
		elseif(starts_with($directory, 'public: '))
		{
			$directory = path('public') . substr($directory, 8);
		}
		else
		{
			$directory = path('base') . $directory;
		}

		if(!file_exists($directory))
		{
			return $this;
		}

		$this->directory = $directory;

		call_user_func($callback, $this);

		$this->directory = null;

		return $this;
	}

	/**
	 * Adds an asset to the container.
	 *
	 * @param  string  $name
	 * @param  string  $file
	 * @param  array   $dependencies
	 * @return object
	 */
	public function add($name, $file = null, $dependencies = array())
	{
		if(is_null($file) and array_key_exists($name, static::$shared))
		{
			$file = static::$shared[$name];
		}

		$asset = new Asset($name, $file, $dependencies);

		if($asset->exists($this->directory) and !$asset->external())
		{
			$asset->updated = filemtime($asset->directory . DS . $asset->file);
		}

		$this->assets[$this->group()][$name] = $asset;

		return $this;
	}

	/**
	 * Delete an asset from the container.
	 * 
	 * @param  string  $name
	 * @return object
	 */
	public function delete($name)
	{
		if(array_key_exists($name, $this->assets[$this->group]))
		{
			unset($this->assets[$this->group][$name]);
		}

		return $this;
	}

	/**
	 * Add a symlink to the array of symlinks. Configuration symlinks are merged
	 * in prior to rendering of assets.
	 *
	 * @param  string  $symlink
	 * @param  string  $target
	 * @return object
	 */
	public function symlink($symlink, $target)
	{
		$this->symlinks[$symlink] = $target;

		return $this;
	}

	/**
	 * Set the group to be used when compiling assets.
	 *
	 * @param  string  $group
	 * @return object
	 */
	public function show($group)
	{
		if(in_array($group, array('styles', 'scripts')))
		{
			$this->group = $group;
		}

		return $this;
	}

	/**
	 * Enables compression for the current container.
	 *
	 * @return object
	 */
	public function compress()
	{
		$this->config->set('compression.enabled', true);

		return $this;
	}

	/**
	 * Enables inline displaying for the current container.
	 *
	 * @return object
	 */
	public function inline()
	{
		$this->config->set('inline', true);

		return $this;
	}

	/**
	 * Enables development mode for the current container.
	 *
	 * @return object
	 */
	public function development()
	{
		$this->config->set('development', true);

		return $this;
	}

	/**
	 * Enables caching for the current container.
	 *
	 * @param  int  $time
	 * @return object
	 */
	public function remember($time = -1)
	{
		$this->cache->time = ($time > 0) ? $time : $this->config->get('caching.time');

		return $this;
	}

	/**
	 * Basset will clear the cache and/or compiled asset.
	 *
	 * @return object
	 */
	public function forget()
	{
		$this->config->set('caching.forget', true);

		return $this;
	}

	/**
	 * Compiles the scripts for the current container.
	 * 
	 * @return string
	 */
	public function scripts()
	{
		return $this->compile('scripts');
	}

	/**
	 * Compiles the styles for the current container.
	 * 
	 * @return string
	 */
	public function styles()
	{
		return $this->compile('styles');
	}

	/**
	 * Compiles and returns the registered assets.
	 *
	 * @param  string  $group
	 * @return string
	 */
	public function compile()
	{
		$group = $this->group();

		$assets = array();

		if($this->config->get('development'))
		{
			$method = Str::singular($group);

			foreach($this->arrange($this->assets[$group]) as $asset)
			{
				$assets[] = HTML::$method($asset->url);
			}

			return implode("\n", $assets);
		}

		if($this->cache->exists($this->config->get('caching.forget')))
		{
			$assets = $this->cache->get();
		}
		else
		{
			$recompile = true;

			if(file_exists($compiled = $this->config->get('compiling.directory') . DS . $this->cache->name()))
			{
				if($this->newest($group) < filemtime($compiled) and !$this->config->get('compiling.recompile'))
				{
					$assets = file_get_contents($compiled);

					$recompile = false;
				}
			}

			if($recompile)
			{
				// Merge in the configuration symlinks to the current array of symlinks so that
				// they can be passed onto each asset so when fetching CSS files the symlinks
				// are available.
				$symlinks = array_merge($this->symlinks, $this->config->get('symlinks'));

				$route = substr(str_replace(array(Bundle::option('basset', 'handles') . '/', File::extension($this->route)), '', $this->route), 0, -1);

				foreach($this->arrange($this->assets[$group]) as $asset)
				{
					$contents = $asset->get($this->config->get('less.php'), $symlinks, $this->config->get('document_root'));

					// Fire the basset.<route>: <file> event until we receive a response. That response
					// will then be used for the asset contents.
					if(!is_null($response = Event::until('basset.' . $route . ': ' . $asset->file, array($contents))))
					{
						$contents = $response;
					}

					$assets[] = $contents;
				}

				$assets = implode('', $assets);

				// If compression is enabled then compress the assets according to the group that
				// is being rendered. Compression is done after combining of all files to save on
				// running the compression on each file. This is ensures that the file is
				// compressed before being cached.
				if($this->config->get('compression.enabled'))
				{
					if($group == 'styles')
					{
						$assets = Vendor\CSSCompress::process($assets, array('preserve_lines' => $this->config->get('compression.preserve_lines')));
					}
					elseif($group == 'scripts')
					{
						$assets = Vendor\JSMin::minify($assets);
					}
				}

				file_put_contents($compiled, $assets);
			}

			// If caching is enabled the cache will be run now and the current copy stored so
			// that it can be loaded quicker on the next request. This is handy for compressed
			// assets once an application has been deployed.
			if($this->cache->time > 0)
			{
				$this->cache->store($assets);
			}
		}

		// If displaying the assets inline this wraps the assets in the correct tags for both
		// stylesheets and javascript assets.
		if($this->config->get('inline'))
		{
			if($group == 'styles')
			{
				$assets = '<style type="text/css" media="all">' . $assets . '</style>';
			}
			else
			{
				$assets = '<script type="text/javascript">' . $assets . '</script>';
			}
		}

		return $assets;
	}

	/**
	 * Determines the group to be used.
	 * 
	 * @return string
	 */
	protected function group()
	{
		if(is_null($this->group))
		{
			if(empty($this->assets['scripts']) and !empty($this->assets['styles']))
			{
				$this->group = 'styles';
			}
			elseif(empty($this->assets['styles']) and !empty($this->assets['scripts']))
			{
				$this->group = 'scripts';
			}
			else
			{
				$this->group = 'styles';
			}
		}

		return $this->group;
	}

	/**
	 * Determine the newest file to be compiled.
	 *
	 * @param  string  $group
	 * @return int
	 */
	protected function newest($group)
	{
		$newest = 0;

		foreach($this->assets[$group] as $asset)
		{
			if($asset->updated > $newest)
			{
				$newest = $asset->updated;
			}
		}

		return $newest;
	}

	/**
	 * Sort and retrieve assets based on their dependencies
	 *
	 * @param  array  $assets
	 * @return array
	 */
	protected function arrange($assets)
	{
		list($original, $sorted) = array($assets, array());

		while (count($assets) > 0)
		{
			foreach ($assets as $asset => $value)
			{
				$this->evaluate_asset($asset, $value, $original, $sorted, $assets);
			}
		}

		return $sorted;
	}

	/**
	 * Evaluate an asset and its dependencies.
	 *
	 * @param  string  $asset
	 * @param  tring  $value
	 * @param  array   $original
	 * @param  array   $sorted
	 * @param  array   $assets
	 * @return void
	 */
	protected function evaluate_asset($asset, $value, $original, &$sorted, &$assets)
	{
		// If the asset has no more dependencies, we can add it to the sorted list
		// and remove it from the array of assets. Otherwise, we will not verify
		// the asset's dependencies and determine if they've been sorted.
		if (count($assets[$asset]->dependencies) == 0)
		{
			$sorted[$asset] = $value;

			unset($assets[$asset]);
		}
		else
		{
			foreach ($assets[$asset]->dependencies as $key => $dependency)
			{
				if ( ! $this->dependency_is_valid($asset, $dependency, $original, $assets))
				{
					unset($assets[$asset]->dependencies[$key]);

					continue;
				}

				// If the dependency has not yet been added to the sorted list, we can not
				// remove it from this asset's array of dependencies. We'll try again on
				// the next trip through the loop.
				if ( ! isset($sorted[$dependency])) continue;

				unset($assets[$asset]->dependencies[$key]);
			}
		}
	}

	/**
	 * Verify that an asset's dependency is valid.
	 *
	 * A dependency is considered valid if it exists, is not a circular reference, and is
	 * not a reference to the owning asset itself. If the dependency doesn't exist, no
	 * error or warning will be given. For the other cases, an exception is thrown.
	 *
	 * @param  string  $asset
	 * @param  string  $dependency
	 * @param  array   $original
	 * @param  array   $assets
	 * @return bool
	 */
	protected function dependency_is_valid($asset, $dependency, $original, $assets)
	{
		if ( ! isset($original[$dependency]))
		{
			return false;
		}
		elseif ($dependency === $asset)
		{
			throw new Exception("Asset [$asset] is dependent on itself.");
		}
		elseif (isset($assets[$dependency]) and in_array($asset, $assets[$dependency]['dependencies']))
		{
			throw new Exception("Assets [$asset] and [$dependency] have a circular dependency.");
		}

		return true;
	}

	/**
	 * Magic method for converting the object to a string. Simply shows the compiled assets.
	 * 
	 * @return string
	 */
	public function __toString()
	{
		return $this->compile();
	}

}
