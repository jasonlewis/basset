<?php namespace Basset; use URL, File, Cache, Config, Bundle, Exception;

/**
 * Basset
 *
 * Basset is a Better Asset manager. Basset is based off the Laravel Asset class
 * but includes many other features such as route based loading, compression and caching.
 *
 * @package 	Basset
 * @author 		Jason Lewis
 * @copyright 	2011-2012 Jason Lewis
 * @email		jason.lewis1991 [at] gmail [dot] com
 */

class Basset {
	/**
	 * @var array $containers
	 */
	protected static $containers = array();

	/**
	 * @var array $routed
	 */
	protected static $routed = false;

	/**
	 * @var array $folders
	 */
	public static $folders = array(
		'style'		=> 'css',
		'script'	=> 'js'
	);

	/**
	 * container
	 *
	 * Creates a new Basset_Container
	 *
	 * @param  string $name
	 * @return Basset_Container
	 */
	public static function container($name = 'default')
	{
		if(isset(static::$containers[$name]))
		{
			return static::$containers[$name];
		}

		// Create a new instance.
		return static::$containers[$name] = new Basset_Container($name, static::$routed);
	}

	/**
	 * routed
	 *
	 * Sets all Basset containers to run in the routed mode.
	 *
	 * @return void
	 */
	public static function routed()
	{
		static::$routed = true;
	}

	/**
	 * __callStatic
	 *
	 * This method invokes unreachable static methods via the default container.
	 *
	 * @param  string $method
	 * @param  array  $arguments
	 * @return Basset_Container
	 */
	public static function __callStatic($method, $arguments)
	{
		return call_user_func_array(array(static::container(), $method), $arguments);
	}

}

/**
 * Basset_Container
 *
 * The main class for Basset operations. The __callStatic() method in Basset
 * defers all calls to it's Basset_Container counterpart.
 */
class Basset_Container {

	/**
	 * @var string $name
	 */
	protected $name;

	/**
	 * @var string $type
	 */
	protected $type;

	/**
	 * @var object $cache
	 */
	protected $cache;

	/**
	 * @var array $assets
	 */
	protected $assets = array();

	/**
	 * @var array $settings
	 */
	protected $settings = array();

	/**
	 * __construct
	 *
	 * Loads the config and sets up some basic data.
	 *
	 * @param  string  $name
	 * @param  bool    $routed
	 * @return Basset_Container
	 */
	public function __construct($name, $routed)
	{
		$this->name = $name;

		$this->cache = new Basset_Cache;

		$this->settings = array_merge(Config::get('basset::basset'), array(
			'forget'	=> false,
			'inline'	=> false,
			'routed'	=> $routed
		));

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
	 * @return Basset_Container
	 */
	public function add($name, $file, $dependencies = array())
	{
		$type = (pathinfo($file, PATHINFO_EXTENSION) == 'css') ? 'style' : 'script';

		// If this asset is prepended with the name of a bundle then we'll update
		// the file to reflect the path to the bundle source.
		if(strpos($file, '::') !== false)
		{
			list($bundle, $file) = explode('::', $file);

			$source = Bundle::assets($bundle);
		}
		else
		{
			$source = URL::base() . '/';
		}

		$dependencies = (array) $dependencies;

		$this->assets[$type][$name] = compact('file', 'source', 'dependencies');

		return $this;
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
		return $this->group('style');
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
		return $this->group('script');
	}

	/**
	 * render
	 *
	 * Renders all the assets for the given types and container. Minifies when required.
	 * Also loads cache if available.
	 *
	 * @param  string  $group
	 * @return string
	 */
	protected function group($group)
	{
		if(!isset($this->assets[$group]) || count($this->assets[$group]) == 0)
		{
			return '';
		}

		$this->cache->register($this->assets, $group, $this->settings['forget']);

		// If this group of assets has a cached copy we'll use the cached version. If the
		// cache is set to be forgotten it will be cleared and a new copy will be returned
		// instead.
		if(!$assets = $this->cache->get())
		{
			$assets = '';
			
			foreach($this->arrange($this->assets[$group]) as $name => $data)
			{
				$assets .= $this->asset($name, $group);
			}

			// If compression is enabled then compress the assets according to the group that
			// is being rendered. Compression is done after combining of all files to save on
			// running the compression on each file. This is ensures that the file is
			// compressed before being cached.
			if($this->settings['compress'])
			{
				if($group == 'style')
				{
					$assets = Libs\CSSCompress::process($assets, array('preserve_lines' => $this->settings['preserve_lines']));
				}
				elseif($group == 'script')
				{
					$assets = Libs\JSMin::minify($assets);
				}
			}

			// If caching is enabled the cache will be run now and the current copy stored so
			// that it can be loaded quicker on the next request. This is handy for compressed
			// assets once an application has been deployed.
			if($this->settings['caching'])
			{
				$this->cache->run($assets, $this->settings['cache_for']);
			}
		}

		// If displaying the assets inline this wraps the assets in the correct tags for both
		// stylesheets and javascript assets.
		if($this->settings['inline'])
		{
			if($group == 'style')
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
	 * asset
	 *
	 * Gets the contents of an asset and if it's a stylesheet it is run through the
	 * URI rewriter to correct any ill-formed directories.
	 *
	 * @param  string  $name
	 * @param  string  $group
	 * @return string
	 */
	protected function asset($name, $group)
	{
		$asset = $this->assets[$group][$name];

		if(!parse_url($asset['file'], PHP_URL_SCHEME))
		{
			if(!file_exists($path = path('public') . str_replace(URL::base() . '/', '', $asset['source']) . Basset::$folders[$group] . '/' . $asset['file']))
			{
				// Could not locate the asset file. Probably named incorrect. To avoid cluttering
				// it up with 404 not found we'll return a commented error.
				return PHP_EOL . '/* Basset could not find asset [' . $path . '] */' . PHP_EOL;
			}

			$asset['file'] = $asset['source'] . Basset::$folders[$group] . '/' . $asset['file'];
		}

		$contents = file_get_contents($asset['file']);

		if(($this->settings['compress'] || $this->settings['routed']) && $group == 'style')
		{
			$contents = Libs\URIRewriter::rewrite($contents, dirname(str_replace($asset['source'], '', $asset['file'])));
		}

		return $contents . PHP_EOL;
	}

	/**
	 * compress
	 *
	 * Sets Basset to use compression on scripts and styles.
	 *
	 * @return Basset_Container
	 */
	public function compress()
	{
		$this->settings['compress'] = true;

		return $this;
	}

	/**
	 * inline
	 *
	 * Sets Basset to render assets inline. This will combine files.
	 *
	 * @return Basset_Container
	 */
	public function inline()
	{
		$this->settings['inline'] = true;

		return $this;
	}

	/**
	 * remember
	 *
	 * Sets Basset to cache the files.
	 *
	 * @return Basset_Container
	 */
	public function remember()
	{
		$this->settings['caching'] = true;

		return $this;
	}

	/**
	 * forget
	 *
	 * Sets Basset to clear the cache.
	 *
	 * @return Basset_Container
	 */
	public function forget()
	{
		$this->settings['forget'] = true;

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
		if (count($assets[$asset]['dependencies']) == 0)
		{
			$sorted[$asset] = $value;

			unset($assets[$asset]);
		}
		else
		{
			foreach ($assets[$asset]['dependencies'] as $key => $dependency)
			{
				if ( ! $this->dependency_is_valid($asset, $dependency, $original, $assets))
				{
					unset($assets[$asset]['dependencies'][$key]);

					continue;
				}

				// If the dependency has not yet been added to the sorted list, we can not
				// remove it from this asset's array of dependencies. We'll try again on
				// the next trip through the loop.
				if ( ! isset($sorted[$dependency])) continue;

				unset($assets[$asset]['dependencies'][$key]);
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
		$group = null;

		if(array_key_exists('style', $this->assets) && array_key_exists('script', $this->assets))
		{
			$group = 'style';
		}
		else
		{
			$group = array_key_exists('style', $this->assets) ? 'style' : 'script';
		}

		return $this->group($group);
	}

}

class Basset_Cache {

	/**
	 * @var array $assets
	 */
	protected $assets = array();

	/**
	 * @var string $group
	 */
	protected $group;

	/**
	 * @var bool $forget
	 */
	protected $forget;

	/**
	 * register
	 *
	 * Registers the assets and the group that is being rendered.
	 *
	 * @param  array   $assets
	 * @param  string  $group
	 * @param  bool    $forget
	 */
	public function register($assets, $group, $forget)
	{
		$this->assets = $assets;

		$this->group = $group;

		$this->forget = $forget;
	}

	/**
	 * has
	 *
	 * Checks if the current group of assets has a cached copy.
	 *
	 * @return bool
	 */
	public function has()
	{
		return Cache::has($this->name());
	}

	/**
	 * get
	 *
	 * Get a cached copy of the group of assets. If the assets are set to be
	 * forgotten the cached copy will not be returned.
	 *
	 * @return mixed
	 */
	public function get()
	{
		if($this->has())
		{
			$assets = Cache::get($name = $this->name());

			if($this->forget)
			{
				Cache::forget($name);

				// We don't want to return the cached assets because we cleared
				// the cache and we want a new fresh copy of the assets returned.
				return false;
			}
		}

		return $assets;
	}

	/**
	 * run
	 *
	 * Runs the cache and stores it if the cache has not already been set.
	 *
	 * @param  string  $assets
	 * @param  int     $minutes
	 */
	public function run($assets, $minutes)
	{
		if(!$this->has())
		{
			Cache::put($this->name(), $assets, $minutes);
		}
	}

	/**
	 * name
	 *
	 * Determines the cached name of the group of assets.
	 *
	 * @return string
	 */
	protected function name()
	{
		$name = array();

		foreach($this->assets[$this->group] as $asset)
		{
			$name[] = $asset['source'] . Basset::$folders[$this->group] . '/' . $asset['file'];
		}

		sort($name);

		return 'basset_' . $this->group . '_' . md5(implode('', $name));
	}
}