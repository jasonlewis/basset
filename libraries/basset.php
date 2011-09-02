<?php namespace Basset;

/**
 * Basset
 *
 * Basset is a Better Asset handler Basset is based off the Laravel Asset class
 * but includes many other features such as combining of files, route based loading,
 * compression, pathing, and caching.
 *
 * @package Basset
 * @author Jason Lewis
 * @copyright 2011 Jason Lewis
 * @url http://jasonlewis.me/projects/basset
 */

class Basset {
	/**
	 * @var array Array of containers
	 */
	protected static $containers = array();

	/**
	 * @var array Array of settings to be passed to all containers.
	 */
	protected static $settings = array();

	/**
	 * container
	 *
	 * Creates a new Basset_Container
	 *
	 * @static
	 * @param string $name
	 * @return Basset_Container
	 */
	public static function container($name = 'default')
	{
		if(isset(static::$containers[$name]))
		{
			return static::$containers[$name];
		}

		// Create a new instance.
		return static::$containers[$name] = new Basset_Container($name, static::$settings);
	}

	/**
	 * routed
	 *
	 * Sets all Basset containers to run in the routed mode.
	 *
	 * @static
	 * @return void
	 */
	public static function routed()
	{
		static::$settings['routed'] = true;
	}

	/**
	 * __callStatic
	 *
	 * Inspired by Laravel's Asset class, this method invokes unreachable static methods
	 * via the default container.
	 *
	 * @static
	 * @param $method
	 * @param $args
	 * @return Basset_Container
	 */
	public static function __callStatic($method, $args)
	{
		return call_user_func_array(array(static::container(), $method), $args);
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
	 * @var string The name of the container.
	 */
	protected $container;

	/**
	 * @var string The type of files that are to be displayed.
	 */
	protected $type;

	/**
	 * @var array Array of paths relative to the public directory.
	 */
	protected $paths = array();

	/**
	 * @var array Array of assets that have been loaded.
	 */
	protected $assets = array();

	/**
	 * @var array Array of css and js folder names.
	 */
	protected $folders = array();

	/**
	 * @var array Array of settings defined in config.
	 */
	protected $settings = array();

	/**
	 * __construct
	 *
	 * Loads the config and sets up some basic data.
	 *
	 * @param string $name Name of container.
	 * @param array $settings Array of settings passed in.
	 * @return Basset_Container
	 */
	public function __construct($name, $settings)
	{
		$this->container = $name;
		$this->folders = array(
			'style'		=> \System\Config::get('basset.folders.css', 'css'),
			'script'	=> \System\Config::get('basset.folders.js', 'js'),
		);
		$this->settings = array_merge(array(
			'combine' 			=> \System\Config::get('basset.combine', false),
			'compress'			=> \System\Config::get('basset.compress', false),
			'caching'			=> \System\Config::get('basset.caching', false),
			'cache_for'			=> \System\Config::get('basset.cache_for', 44640),
			'preserve_lines'	=> \System\Config::get('basset.preserve_lines', false),
			'forget'			=> false,
			'inline'			=> false,
			'routed'			=> false
		), $settings);

		foreach(\System\Config::get('basset.paths') as $name => $path)
		{
			$this->path($name, $path);
		}

		if(!isset($this->paths['default']))
		{
			throw new \System\Exception('You need to specify a default path for Basset');
		}

		return $this;
	}

	/**
	 * add
	 *
	 * Adds an asset.
	 *
	 * @param string $name Name of the new asset.
	 * @param string $file The filename of the asset, relative to either js or css.
	 * @param array $dependencies Array of asset names this asset depends on (order of loading).
	 * @return Basset_Container
	 */
	public function add($name, $file, $dependencies = array())
	{
		$type = \System\File::extension($file) == 'css' ? 'style' : 'script';

		// Determine if the file is namespaced.
		$namespace = 'default';
		if(strpos($file, '::') !== false)
		{
			list($namespace, $file) = explode('::', $file);

			if(!$this->is_path($namespace)) throw new \Exception('Invalid namespace for ' . $namespace . '::' . $file);
		}

		$dependencies = (array) $dependencies;
		$this->assets[$type][$name] = compact('file', 'namespace', 'dependencies');
		return $this;
	}

	/**
	 * path
	 *
	 * Adds a path if it does not already exist.
	 *
	 * @param string $name Name of path to add.
	 * @param string $path Path relative to the public directory
	 * @return Basset_Container
	 */
	public function path($name, $path)
	{
		if(!$this->is_path($name))
		{
			if(substr($path, 0, 1) === '/') $path = substr($path, 1);
			if(substr($path, -1) !== '/') $path .= '/';
			
			$this->paths[$name] = $path;
		}
		return $this;
	}

	/**
	 * styles
	 *
	 * Sets Basset to use styles.
	 *
	 * @return Basset_Container
	 */
	public function styles()
	{
		$this->type = 'style';
		return $this;
	}

	/**
	 * scripts
	 *
	 * Sets Basset to use scripts.
	 *
	 * @return Basset_Container
	 */
	public function scripts()
	{
		$this->type = 'script';
		return $this;
	}

	/**
	 * get_asset
	 *
	 * Fetches an asset file.
	 *
	 * @param array $call Class and method to call
	 * @param string $type Type of file.
	 * @param string $file The file path.
	 * @param string $namespace The namespace to use for the file.
	 * @return string
	 */
	public function get_asset($call, $type, $file, $namespace = null)
	{
		if(!parse_url($file, PHP_URL_SCHEME))
		{
			// If it's not a well-formed URL than it's part of the local directory.
			$file = ($this->is_path($namespace) ? $this->get_path($namespace) : '') . $this->folders[$type] . '/' . $file;
		}
		return call_user_func_array($call, array($file));
	}

	/**
	 * determine
	 *
	 * Determines what container and filetype shall be used. Generally only called by
	 * the __toString method if either of the settings is not yet set.
	 *
	 * @return Basset_Container
	 */
	public function determine()
	{
		if(is_null($this->type))
		{
			$styles = isset($this->assets['style']);
			$scripts = isset($this->assets['script']);

			$this->type = $styles && $scripts ? 'style' : ($styles && !$scripts ? 'style' : 'script');
		}
		return $this;
	}

	/**
	 * combine
	 *
	 * Sets Basset to combine the files, overrides the config.
	 *
	 * @return Basset_Container
	 */
	public function combine()
	{
		$this->settings['combine'] = true;
		return $this;
	}

	/**
	 * compress
	 *
	 * Sets Basset to use compression, overrides the config.
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
	 * Sets Basset to render assets inline.
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
	 * Sets Basset to clear the cache when ready.
	 *
	 * @return Basset_Container
	 */
	public function forget()
	{
		$this->settings['forget'] = true;
		return $this;
	}

	/**
	 * get_path
	 *
	 * Returns the path for a given path name.
	 *
	 * @param string $path Name of path to retrieve
	 * @return string
	 */
	public function get_path($path)
	{
		if(!isset($this->paths[$path])) return '';
		return $this->paths[$path];
	}

	/**
	 * is_path
	 *
	 * Checks if a path is valid.
	 *
	 * @param string $path Name of path to check.
	 * @return bool
	 */
	protected function is_path($path)
	{
		return isset($this->paths[$path]);
	}

	/**
	 * render
	 *
	 * Renders all the assets for the given types and container. Minifies when required.
	 * Also loads cache if available.
	 *
	 * @return string
	 */
	protected function render()
	{
		if(!$assets = $this->get_cache())
		{
			if(!isset($this->assets[$this->type]) || count($this->assets[$this->type]) == 0) return '';

			// Fetch the assets.
			$assets = $this->fetch($this->assets);

			// Run the caching mechanism.
			$this->do_cache($assets);
		}

		if($this->settings['inline']) $assets = call_user_func_array(array($this, 'inline_' . $this->type), array($assets));
		return $assets;
	}

	/**
	 * fetch
	 *
	 * Fetches assets for the given type.
	 *
	 * @param array $assets Array of assets to fetch from.
	 * @return string
	 */
	protected function fetch($assets)
	{
		$call = array('\System\\File', 'get');
		if(!$this->settings['routed'] && !$this->settings['inline'])
		{
			$call = array('\System\\HTML', $this->type);
		}

		$compress_css = $compress_js = null;
		if(is_array($this->settings['compress'])) extract($this->settings['compress'], EXTR_PREFIX_ALL, 'compress_');

		$return = '';
		foreach($this->arrange($assets[$this->type]) as $data)
		{
			$contents = $this->get_asset($call, $this->type, $data['file'], $data['namespace']);

			if($this->type == 'style' && ($this->settings['compress'] === true || $compress_css === true || $this->settings['routed'] === true || $this->settings['combine'] === true))
			{
				// We need to rewrite URIs if combining or compressing CSS files.
				// Compression results in all files being combined regardless of the combined setting.
				$file = $this->get_filename($data, $this->type);
				$contents = Libs\URIRewriter::rewrite($contents, dirname($file));
			}

			$return .= $contents;
		}

		if($this->settings['compress'] === true)
		{
			switch($this->type)
			{
				case 'style':
					if(!isset($compress_css) || $compress_css === true)
					{
						$return = Libs\CSSCompress::process($return, array('preserve_lines' => $this->settings['preserve_lines']));
					}
				break;
				case 'script':
					if(!isset($compress_js) || $compress_js === true)
					{
						$return = Libs\JSMin::minify($return);
					}
				break;
			}
		}

		return $return;
	}

	/**
	 * get_filename
	 *
	 * Returns the full name of the file with prefixed namespace.
	 *
	 * @param array $data Singular asset data array.
	 * @param string $type Type of asset.
	 * @return string
	 */
	protected function get_filename($data, $type)
	{
		return ($this->is_path($data['namespace']) ? $this->get_path($data['namespace']) : '') . $this->folders[$type] . '/' . $data['file'];
	}

	/**
	 * inline_style
	 *
	 * Converts the asset contents into the inline style tag. Not ideal using the HTML in here
	 * but it saves calling a View.
	 *
	 * @param string $contents Contents to embed within the inline tag.
	 * @return string
	 */
	protected function inline_style($contents)
	{
		return '<style type="text/css" media="all">' . PHP_EOL . $contents . PHP_EOL . '</style>';
	}

	/**
	 * inline_script
	 *
	 * Converts the asset contents into the inline script tag. Not ideal using the HTML in here
	 * but it saves calling a View.
	 *
	 * @param string $contents Contents to embed within the inline tag.
	 * @return string
	 */
	protected function inline_script($contents)
	{
		return '<script type="text/javascript">' . PHP_EOL . $contents . PHP_EOL . '</script>';
	}

	/**
	 * arrange
	 *
	 * Arranges assets based on dependencies
	 *
	 * @author Taylor Otwell (from the Asset class)
	 * @param array $assets Array of assets to arrange
	 * @return array
	 */
	protected function arrange($assets)
	{
		list($original, $sorted) = array($assets, array());

		while(count($assets) > 0)
		{
			foreach($assets as $name => $data)
			{
				$this->evaluate_asset($name, $data, $original, $sorted, $assets);
			}
		}

		return $sorted;
	}

	/**
	 * evaluate_asset
	 *
	 * Evaluate an asset and its dependencies.
	 *
	 * @author Taylor Otwell (from the Asset class)
	 * @param string $name Name of asset to evaluate
	 * @param array $data Array of asset information.
	 * @param array $original Array of original assets.
	 * @param array $sorted Array of sorted assets.
	 * @param array $assets
	 * @return void
	 */
	protected function evaluate_asset($name, $data, $original, &$sorted, &$assets)
	{
		if(count($assets[$name]['dependencies']) == 0)
		{
			$sorted[$name] = $data;
			unset($assets[$name]);
		}
		else
		{
			foreach($assets[$name]['dependencies'] as $key => $dependency)
			{
				if(!$this->valid_dependency($name, $dependency, $original, $assets))
				{
					unset($assets[$name]['dependencies'][$key]);
					continue;
				}

				if(!isset($sorted[$dependency])) continue;

				unset($assets[$name]['dependencies'][$key]);
			}
		}
	}

	/**
	 * valid_dependency
	 * Check that a dependency is valid.
	 *
	 * @author Taylor Otwell (from the Asset class)
	 * @param string $name Name of the asset
	 * @param string $dependency Name of dependency
	 * @param array $original
	 * @param array $assets
	 * @return bool
	 */
	protected function valid_dependency($name, $dependency, $original, $assets)
	{
		if ( ! isset($original[$dependency]))
		{
			return false;
		}
		elseif ($dependency === $name)
		{
			throw new \Exception("Asset [$name] is dependent on itself.");
		}
		elseif (isset($assets[$dependency]) and in_array($name, $assets[$dependency]['dependencies']))
		{
			throw new \Exception("Assets [$name] and [$dependency] have a circular dependency.");
		}
	}

	/**
	 * get_cache
	 *
	 * Returns false if no cache found, otherwise returns the cached files.
	 * Also clears the cache of they specified to do so.
	 *
	 * @return bool|string
	 */
	protected function get_cache()
	{
		$name = $this->get_cache_name();
		if(!$cache = \System\Cache::get('basset_' . $this->type . '_' . $name, false)) return false;

		// Clearing the cache?
		if($this->settings['forget'])
		{
			\System\Cache::forget('basset_' . $this->type . '_' . $name);
			return false;
		}

		return $cache;
	}

	/**
	 * do_cache
	 *
	 * Stores the assets in the cache of the option is set.
	 *
	 * @param string $assets
	 * @return void
	 */
	protected function do_cache($assets)
	{
		$name = $this->get_cache_name();
		if($this->settings['caching'] === false || \System\Cache::has('basset_' . $this->type . '_' . $name)) return false;
		
		// Cache the assets.
		\System\Cache::put('basset_' . $this->type . '_' . $name, $assets, $this->settings['cache_for']);
	}

	/**
	 * get_cache_name
	 *
	 * Gets the name of the cache based on the actual filenames sorted alphabetically and cached.
	 *
	 * @return string
	 */
	protected function get_cache_name()
	{
		$name = array();
		foreach($this->assets[$this->type] as $data)
		{
			$name[] = $this->get_filename($data, $this->type);
		}
		sort($name);
		
		return md5(implode('', $name));
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
		return $this->determine()->render();
	}

}