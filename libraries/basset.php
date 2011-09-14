<?php namespace Basset;

/**
 * Basset
 *
 * Basset is a Better Asset handler Basset is based off the Laravel Asset class
 * but includes many other features such as combining of files, route based loading,
 * compression, pathing, caching, and LESS compatibility.
 *
 * @package   Basset
 * @author    Jason Lewis
 * @copyright 2011 Jason Lewis
 * @version   1.1
 * @url       http://jasonlewis.me/projects/basset
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
		return static::$containers[$name] = new Basset_Container($name, static::$settings);
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
		static::$settings['routed'] = true;
	}

	/**
	 * __callStatic
	 *
	 * Inspired by Laravel's Asset class, this method invokes unreachable static methods
	 * via the default container.
	 *
	 * @param  $method
	 * @param  $args
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
	 * @var array Array of assets to render based on the type selected.
	 */
	protected $assets_to_render;

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
	 * @param  string $name    Name of container.
	 * @param  array $settings Array of settings passed in.
	 * @return Basset_Container
	 */
	public function __construct($name, $settings)
	{
		$this->container = $name;

		$this->folders = array(
			'script'	=> \System\Config::get('basset::basset.folders.js', 'js'),
			'style'		=> \System\Config::get('basset::basset.folders.css', 'css'),
			'less'		=> \System\Config::get('basset::basset.folders.less', 'less'),
		);

		$this->settings = array_merge(array(
			'combine' 			=> \System\Config::get('basset::basset.combine', false),
			'compress'			=> \System\Config::get('basset::basset.compress', false),
			'caching'			=> \System\Config::get('basset::basset.caching', false),
			'cache_for'			=> \System\Config::get('basset::basset.cache_for', 44640),
			'preserve_lines'	=> \System\Config::get('basset::basset.preserve_lines', false),
			'less'				=> \System\Config::get('basset::basset.less'),
			'forget'			=> false,
			'inline'			=> false,
			'routed'			=> false
		), $settings);

		if(is_bool($this->settings['compress']))
		{
			$this->settings['compress'] = array(
				'style' 	=> $this->settings['compress'],
				'script'	=> $this->settings['compress']
			);
		}

		foreach(\System\Config::get('basset::basset.paths') as $name => $path)
		{
			$this->path($name, $path);
		}

		if(!isset($this->paths['default']))
		{
			throw new \System\Exception('You need to specify a default path for Basset.');
		}

		return $this;
	}

	/**
	 * add
	 *
	 * Adds an asset.
	 *
	 * @param  string $name         Name of the new asset.
	 * @param  string $file         The filename of the asset, relative to either js or css.
	 * @param  array  $dependencies Array of asset names this asset depends on (order of loading).
	 * @return Basset_Container
	 */
	public function add($name, $file, $dependencies = array())
	{
		$type = in_array($extension = \System\File::extension($file), array('css', 'less')) ? 'style' : 'script';

		$path = 'default';
		
		if(strpos($file, '::') !== false)
		{
			list($path, $file) = explode('::', $file);

			if(!$this->is_path($path))
			{
				throw new \Exception('Invalid path for ' . $path . '::' . $file);
			}
		}

		$dependencies = (array) $dependencies;
		$less = $extension == 'less';

		$this->assets[$type][$name] = compact('file', 'path', 'dependencies', 'less', 'type');

		return $this;
	}

	/**
	 * path
	 *
	 * Adds a path if it does not already exist.
	 *
	 * @param  string $name Name of path to add.
	 * @param  string $path Path relative to the public directory
	 * @return Basset_Container
	 */
	public function path($name, $path)
	{
		if(!$this->is_path($name))
		{
			if(substr($path, 0, 1) === '/')
			{
				$path = substr($path, 1);
			}

			if(substr($path, -1) !== '/')
			{
				$path .= '/';
			}
			
			$this->paths[$name] = $path;
		}
		return $this;
	}

	/**
	 * styles
	 *
	 * Sets Basset to use styles only.
	 *
	 * @return Basset_Container
	 */
	public function styles()
	{
		if(!isset($this->assets['style']))
		{
			throw new \Exception('Could not switch Basset to use styles for output because no styles have been added.');
		}

		$this->assets_to_render = array('style', $this->assets['style']);

		return $this;
	}

	/**
	 * scripts
	 *
	 * Sets Basset to use scripts only.
	 *
	 * @return Basset_Container
	 */
	public function scripts()
	{
		if(!isset($this->assets['script']))
		{
			throw new \Exception('Could not switch Basset to use scripts for output because no scripts have been added.');
		}

		$this->assets_to_render = array('script', $this->assets['script']);

		return $this;
	}

	/**
	 * get_asset
	 *
	 * Fetches an asset file.
	 *
	 * @param  array $asset Array of asset data.
	 * @return string
	 */
	public function get_asset($asset)
	{
		if(!parse_url($asset['file'], PHP_URL_SCHEME))
		{
			// If it's not a well-formed URL than it's part of the local directory.
			$asset['file'] = $this->get_path($asset['path']) . $this->folders[$asset['less'] ? 'less' : $asset['type']] . '/' . $asset['file'];
		}

		$call = array('\System\\File', 'get');
		$params = array($asset['file']);

		if(!$this->settings['routed'] && !$this->settings['inline'])
		{
			$call = array('\System\\HTML', $asset['type']);

			if($asset['less'])
			{
				$params[] = array('rel' => 'stylesheet/less');
			}
		}

		return call_user_func_array($call, $params);
	}

	/**
	 * determine
	 *
	 * Determines the filetype to show if none is manually selected.
	 * Defaults to style of both styles and scripts are present.
	 *
	 * @return Basset_Container
	 */
	public function determine()
	{
		if(is_null($this->assets_to_render))
		{
			if($this->settings['routed'] || $this->settings['inline'])
			{
				if(isset($this->assets['style']) && isset($this->assets['script']))
				{
					throw new \Exception('Basset is not able to render both styles and scripts via route based or inline loading.');
				}

				$this->assets_to_render = isset($this->assets['style']) ? array('style', $this->assets['style']) : array('script', $this->assets['script']);
			}
			else
			{
				$this->assets_to_render = array(isset($this->assets['script']) && isset($this->assets['style']) ? 'both' : (isset($this->assets['script']) ? 'script' : 'style'), array());
				
				foreach($this->assets as $type => $assets)
				{
					foreach($assets as $name => $data)
					{
						array_walk($data['dependencies'], function(&$dependency, $key) use ($type)
						{
							if(strpos($dependency, '::') === true)
							{
								list($user_set_type, $user_file_name) = explode('::', $dependency);

								if(in_array($user_set_type, array('script', 'style')))
								{
									return false;
								}
							}

							$dependency = $type . '::' . $dependency;
						});

						$this->assets_to_render[1][$type . '::' . $name] = $data;
					}
				}
			}
		}

		// Makes it prettier.
		$this->assets_to_render = array(
			'type'		=> $this->assets_to_render[0],
			'assets'	=> $this->assets_to_render[1]
		);

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
	 * @param  int $for Time in hours to cache for. (optional)
	 * @return Basset_Container
	 */
	public function remember($for = null)
	{
		$this->settings['caching'] = true;

		if(!is_null($for))
		{
			$this->settings['cache_for'] = $for;
		}

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
	 * get_path
	 *
	 * Returns the path for a given path name.
	 *
	 * @param  string $path Name of path to retrieve
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
	 * @param  string $path Name of path to check.
	 * @return bool
	 */
	protected function is_path($path)
	{
		return isset($this->paths[$path]);
	}

	/**
	 * render
	 *
	 * Renders all the assets for the given type. First checks for a cached copy of the assets.
	 *
	 * @return string
	 */
	protected function render()
	{
		if(!$assets = $this->get_cache())
		{
			if(!isset($this->assets_to_render['assets']) || count($this->assets_to_render['assets']) == 0)
			{
				return '';
			}

			$assets = $this->fetch();

			$this->do_cache($assets);
		}

		if($this->settings['inline'])
		{
			$assets = $this->inline_assets($assets);
		}

		$this->assets = $this->assets_to_render = array();

		return $assets;
	}

	/**
	 * fetch
	 *
	 * Fetches assets for the given type.
	 *
	 * @return string
	 */
	protected function fetch()
	{
		$compress_style = $compress_script = null;
		extract($this->settings['compress'], EXTR_PREFIX_ALL, 'compress_');

		$finalized = array();
		$compress = array();

		foreach($this->arrange($this->assets_to_render['assets']) as $asset)
		{
			$contents = $this->get_asset($asset);

			if($asset['type'] == 'style' && ($compress_style || $this->settings['routed'] || $this->settings['combine']))
			{
				// We need to rewrite URIs if combining or compressing CSS files. Compression will automatically combine all files regardless of the setting.
				$file = $this->get_filename($asset);
				
				$contents = Libs\URIRewriter::rewrite($contents, dirname($file));
			}

			if($asset['less'] && $this->settings['less']['php_compiler'] && ($this->settings['routed'] || $this->settings['inline']))
			{
				// If using the LessPHP compiler and this file is LESS based, parse the contents.
				// Only if we are using the route based loading or inline display of styles.
				$less = new Libs\LessPHP;
				
				$contents = $less->parse($contents);
			}

			if(($asset['type'] == 'style' && $compress_style) || ($asset['type'] == 'script' && $compress_script) && ($this->settings['routed'] || $this->settings['inline']))
			{
				$compress[$asset['type']][] = $contents;
			}
			else
			{
				$finalized[] = $contents;
			}
		}

		if(isset($compress['style']))
		{
			$finalized[] = Libs\CSSCompress::process(implode(PHP_EOL, $compress['style']), array('preserve_lines' => $this->settings['preserve_lines']));
		}

		if(isset($compress['script']))
		{
			$finalized[] = Libs\JSMin::minify(implode(PHP_EOL, $compress['script']));
		}

		return implode(PHP_EOL, $finalized);
	}

	/**
	 * get_filename
	 *
	 * Returns the full name of the file with prefixed pathname.
	 *
	 * @param  array $data  Singular asset data array.
	 * @return string
	 */
	protected function get_filename($data)
	{
		return $this->get_path($data['path']) . $this->folders[$data['less'] ? 'less' : $data['type']] . '/' . $data['file'];
	}

	/**
	 * inline_assets
	 *
	 * Calls the appropriate inline assets method or fails if trying to show both inline.
	 *
	 * @param  string $contents Contents to embed within tag.
	 * @return mixed
	 */
	protected function inline_assets($contents)
	{
		if($this->assets_to_render['type'] === 'both')
		{
			throw new \Exception('Basset is not able to render both styles and scripts inline when called on the same instance.');
		}

		return call_user_func(array($this, 'inline_' . $this->assets_to_render['type']), $contents);
	}

	/**
	 * inline_style
	 *
	 * Converts the asset contents into the inline style tag. Not ideal using the HTML in here
	 * but it saves calling a View.
	 *
	 * @param  string $contents Contents to embed within the inline tag.
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

		return true;
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

		if(!$cache = \System\Cache::get('basset_' . $name, false))
		{
			return false;
		}

		if($this->settings['forget'])
		{
			\System\Cache::forget('basset_' . $name);

			return false;
		}

		return $cache;
	}

	/**
	 * do_cache
	 *
	 * Stores the assets in the cache if the option is set.
	 *
	 * @param  string $assets
	 * @return void
	 */
	protected function do_cache($assets)
	{
		$name = $this->get_cache_name();

		if(!$this->settings['caching'] || \System\Cache::has('basset_' . $name))
		{
			return false;
		}

		\System\Cache::put('basset_' . $name, $assets, $this->settings['cache_for'] * 60);
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

		if(!isset($this->assets_to_render['assets']))
		{
			return '';
		}

		foreach($this->assets_to_render['assets'] as $data)
		{
			$name[] = $this->get_filename($data);
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