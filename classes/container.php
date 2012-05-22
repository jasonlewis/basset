<?php namespace Basset;

use File, Basset;

class Container {

	/**
	 * @var string $group
	 */
	protected $group;

	/**
	 * @var object $cache
	 */
	protected $cache;

	/**
	 * @var array $assets
	 */
	protected $assets = array();

	/**
	 * @var array $config
	 */
	protected $config;

	/**
	 * @var string $directory
	 */
	protected $directory = null;

	/**
	 * @var array $symlinks
	 */
	protected $symlinks = array();

	/**
	 * __construct
	 *
	 * Loads the config and sets up some basic data.
	 *
	 * @param  string  $group
	 * @return object
	 */
	public function __construct($group = null)
	{
		$this->group = $group;

		$this->cache = new Cache;

		Config::load();

		return $this;
	}

	/**
	 * directory
	 * 
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

			$directory = str_replace(path('base'), '', path('public')) . Basset::corrector(Bundle::assets($bundle)) . $directory;
		}

		if(!file_exists(path('base') . $directory))
		{
			return $this;
		}

		$this->directory = $directory;

		call_user_func($callback, $this);

		$this->directory = null;

		return $this;
	}

	/**
	 * add
	 *
	 * Adds an asset to the container.
	 *
	 * @param  string  $name
	 * @param  string  $file
	 * @param  array   $dependencies
	 * @return object
	 */
	public function add($name, $file, $dependencies = array())
	{
		if(is_null($group = array_key_exists($extension = File::extension($file), Basset::$available) ? Basset::$available[$extension]['group'] : null))
		{
			throw new Exception('Unsupported file group [' . $extension . '] added to Bassset container.');
		}

		$asset = new Asset($name, $file, $group, $extension, $this->directory, $dependencies);

		if($asset->exists() && !$asset->external)
		{
			$asset->updated = filemtime($asset->source . DS . $asset->file);
		}

		$this->assets[$group][$name] = $asset;

		return $this;
	}

	/**
	 * symlink
	 * 
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
	 * group
	 * 
	 * Sets the group, either style or script, to be used when displaying assets.
	 * 
	 * @param  string  $group
	 * @return object
	 */
	public function group($group)
	{
		if(in_array($group, array('styles', 'scripts')))
		{
			$this->group = $group;

			return $this;
		}

		throw new Exception('Unrecognized asset group could not be set in Basset.');
	}

	/**
	 * styles
	 *
	 * Return the registered styles.
	 *
	 * @return string
	 */
	public function styles()
	{
		return $this->render('styles');
	}

	/**
	 * scripts
	 *
	 * Return the registered scripts.
	 *
	 * @return string
	 */
	public function scripts()
	{
		return $this->render('scripts');
	}

	/**
	 * render
	 *
	 * Renders all the assets for the given group.
	 *
	 * @param  string  $group
	 * @return string
	 */
	protected function render($group)
	{
		if(!isset($this->assets[$group]) || count($this->assets[$group]) == 0) return '';

		$assets = array();

		// If we are in development mode then we'll return the respective HTML include form for each
		// asset.
		if(Config::get('development'))
		{
			foreach($this->arrange($this->assets[$group]) as $asset)
			{
				$assets[] = $asset->html();
			}

			return implode('\n', $assets);
		}

		// Register the assets with the cache. This allows the name of the cache and compiled files to be
		// determined as well as the cache to be used.
		$this->cache->register($this->assets, $group, Config::get('caching.forget'));

		if($this->cache->has())
		{
			$assets = $this->cache->get();
		}
		else
		{
			$recompile = true;

			if(file_exists($compiled = (realpath(__DIR__ . DS . '..' . DS . 'compiled') . DS . $this->cache->name())))
			{
				if($this->newest($group) < filemtime($compiled))
				{
					// Assets have not been changed since last compile.
					$assets = file_get_contents($compiled);

					// We no longer want the assets recompiled.
					$recompile = false;
				}
			}

			if($recompile)
			{
				// Merge in the configuration symlinks to the current array of symlinks so that
				// they can be passed onto each asset so when fetching CSS files the symlinks
				// are available.
				$symlinks = array_merge($this->symlinks, Config::get('symlinks'));

				foreach($this->arrange($this->assets[$group]) as $asset)
				{
					$assets[] = $asset->get($symlinks, Config::get('document_root'));
				}

				$assets = implode('', $assets);

				// If compression is enabled then compress the assets according to the group that
				// is being rendered. Compression is done after combining of all files to save on
				// running the compression on each file. This is ensures that the file is
				// compressed before being cached.
				if(Config::get('compression.enabled'))
				{
					if($group == 'styles')
					{
						$assets = Basset\Vendor\CSSCompress::process($assets, array('preserve_lines' => $this->config('compression.preserve_lines')));
					}
					elseif($group == 'scripts')
					{
						$assets = Basset\Vendor\JSMin::minify($assets);
					}
				}

				file_put_contents($compiled, $assets);
			}

			// If caching is enabled the cache will be run now and the current copy stored so
			// that it can be loaded quicker on the next request. This is handy for compressed
			// assets once an application has been deployed.
			if($this->cache->time > 0)
			{
				$this->cache->run($assets);
			}
		}

		// If displaying the assets inline this wraps the assets in the correct tags for both
		// stylesheets and javascript assets.
		if(Config::get('inline'))
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
	 * newest
	 * 
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
	 * compress
	 *
	 * Sets Basset to use compression on scripts and styles.
	 *
	 * @return object
	 */
	public function compress()
	{
		Config::set('compression.enabled', true);

		return $this;
	}

	/**
	 * inline
	 *
	 * Sets Basset to render assets inline. This will combine files.
	 *
	 * @return object
	 */
	public function inline()
	{
		Config::set('inline', true);

		return $this;
	}

	/**
	 * development
	 *
	 * Sets Basset to render assets in development mode.
	 *
	 * @return object
	 */
	public function development()
	{
		Config::set('development', true);

		return $this;
	}

	/**
	 * remember
	 *
	 * Sets Basset to cache the files.
	 *
	 * @param  int  $time
	 * @return object
	 */
	public function remember($time = -1)
	{
		$this->cache->time = ($time > 0) ? $time : Config::get('caching.time');

		return $this;
	}

	/**
	 * forget
	 *
	 * Sets Basset to clear the cache and the compiled assets.
	 *
	 * @return object
	 */
	public function forget()
	{
		Config::set('caching.forget', true);

		return $this;
	}

	/**
	 * arrange
	 *
	 * Sort and retrieve assets based on their dependencies
	 *
	 * @author  Taylor Otwell
	 * @param   array  $assets
	 * @return  array
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
	 * evaluate_asset
	 *
	 * Evaluate an asset and its dependencies.
	 *
	 * @author  Taylor Otwell
	 * @param   string  $asset
	 * @param   string  $value
	 * @param   array   $original
	 * @param   array   $sorted
	 * @param   array   $assets
	 * @return  void
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
	 * dependency_is_valid
	 *
	 * Verify that an asset's dependency is valid.
	 *
	 * A dependency is considered valid if it exists, is not a circular reference, and is
	 * not a reference to the owning asset itself. If the dependency doesn't exist, no
	 * error or warning will be given. For the other cases, an exception is thrown.
	 *
	 * @author  Taylor Otwell
	 * @param   string  $asset
	 * @param   string  $dependency
	 * @param   array   $original
	 * @param   array   $assets
	 * @return  bool
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
	 * __toString
	 *
	 * Render the Basset files.
	 *
	 * @return string
	 */
	public function __toString()
	{
		if(!$this->group)
		{
			$this->group = (isset($this->assets['styles']) && isset($this->assets['scripts']) ? 'styles' : (isset($this->assets['styles']) ? 'styles' : 'scripts'));
		}

		return $this->render($this->group);
	}

}