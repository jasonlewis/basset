<?php

/**
 * Basset
 *
 * Basset is a better asset management bundle for the Laravel PHP framework. Basset allows you to
 * generate asset routes which can be compressed and cached to maximize website performance. Basset
 * also allows compressed and cached assets to appear inline.
 *
 * @package 	Basset
 * @version     1.3.2
 * @author 		Jason Lewis
 * @copyright 	2011-2012 Jason Lewis
 * @link		http://jasonlewis.me/code/basset
 */

class Basset {

	/**
	 * @var array $containers
	 */
	public static $containers = array();

	/**
	 * @var array $available
	 */
	public static $available = array(
		'css' => array(
			'group' 	=> 'styles',
			'extension' => 'css'
		),
		'less' => array(
			'group' 	=> 'styles',
			'extension' => 'css'
		),
		'js' => array(
			'group' 	=> 'scripts',
			'extension' => 'js'
		)
	);

	/**
	 * inline
	 * 
	 * Create a new inline Basset_Container instance or return an existing instance.
	 * 
	 * @param  string  $name
	 * @return Basset_Container
	 */
	public static function inline($name)
	{
		$name = 'inline::' . $name;

		if(isset(static::$containers[$name]))
		{
			return static::$containers[$name];
		}

		static::$containers[$name] = new Basset_Container;

		return static::$containers[$name]->inline();
	}

	/**
	 * valid
	 * 
	 * Iterate through the available formats and return the valid extension.
	 * 
	 * @param  string  $group
	 * @return mixed
	 */
	protected static function valid($group)
	{
		foreach(static::$available as $available)
		{
			if($group == $available['group'])
			{
				return $available['extension'];
			}
		}

		return false;
	}

	/**
	 * __callStatic
	 *
	 * Invokes one of the available containers and generates a new route.
	 *
	 * @param  string  $group
	 * @param  array   $arguments
	 * @return Basset_Container
	 */
	public static function __callStatic($group, $arguments)
	{
		if($extension = static::valid($group))
		{
			list($name, $callback) = $arguments;

			$route = Bundle::option('basset', 'handles') . '/' . $name . '.' . $extension;

			Route::get($route, function() use ($callback, $name, $group, $extension)
			{
				call_user_func($callback, $assets = new Basset_Container($group));

				Basset::$containers[$name] = $assets;

				return $assets;
			});
		}
		else
		{
			throw new BadMethodCallException('Could not find group [' . $group . '] on Basset.');
		}
	}

}

/**
 * Basset_Container
 */
class Basset_Container {

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
	 * @var array $settings
	 */
	protected $settings = array();

	/**
	 * @var string $source
	 */
	protected $source = null;

	/**
	 * __construct
	 *
	 * Loads the config and sets up some basic data.
	 *
	 * @param  string  $group
	 * @return Basset_Container
	 */
	public function __construct($group = null)
	{
		$this->group = $group;

		$this->cache = new Basset_Cache;

		$this->settings = array_merge_recursive(Config::get('basset::basset'), array(
			'caching'	=> array('forget' => false),
			'compiling' => array('forget' => false),
			'inline'	=> false
		));

		return $this;
	}

	/**
	 * directory
	 * 
	 * Create a new directory collection of assets.
	 * 
	 * @param  string   $source
	 * @param  Closure  $callback
	 * @return object
	 */
	public function directory($source, $callback)
	{
		if(strpos($source, '::') !== false)
		{
			list($bundle, $source) = explode('::', $source);

			$source = str_replace(path('base'), '', path('public')) . $this->corrector(Bundle::assets($bundle)) . $source;
		}

		if(!file_exists(path('base') . $source))
		{
			// Could not locate source from the base directory, return nothing.
			return $this;
		}

		$this->source = $source;

		call_user_func($callback, $assets = $this);

		$this->source = null;

		return $this;
	}

	/**
	 * corrector
	 * 
	 * Corrects the end path to be used by Basset.
	 * 
	 * @param  string  $path
	 * @return string
	 */
	protected function corrector($path)
	{
		return substr(str_replace(URL::base(), '', $path), 1);
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
		if(is_null($group = array_key_exists($extension = File::extension($file), Basset::$available) ? Basset::$available[$extension]['group'] : null))
		{
			throw new Exception('Unsupported file group [' . $extension . '] added to Bassset container.');
		}

		$less = ($extension == 'less');

		$dependencies = (array) $dependencies;

		$bundle = $external = false;

		$updated = 0;

		// In order of priority. If using a defined source we'll stick to that,
		// or if we can find a prefixed bundle we'll attempt to use that. Last
		// option is to use the standard public path.
		if(!is_null($this->source))
		{
			$source = path('base') . $this->source;
		}
		elseif(strpos($file, '::') !== false)
		{
			list($bundle, $file) = explode('::', $file);

			$source = path('public') . $this->corrector(Bundle::assets($bundle));

			$bundle = true;
		}
		else
		{
			$source = path('public') . $this->corrector(URL::to_asset('/'));
		}

		// If the source has not been specified the public directory or bundle
		// directory is being used, by default we'll go to the public directory
		// and depending on the asset group we'll add the css or js folder.
		if(is_null($this->source) && strpos($file, '/') == false)
		{
			$source .= ($group == 'styles' ? 'css' : 'js');
		}

		$source = realpath($source);

		$this->assets[$group][$name] = compact('file', 'source', 'dependencies', 'less', 'bundle', 'external', 'updated');

		if($this->find($name, $group) && !$this->assets[$group][$name]['external'])
		{
			$this->assets[$group][$name]['updated'] = filemtime($source . '/' . $file);
		}

		return $this;
	}

	/**
	 * group
	 * 
	 * Sets the group, either style or script, to be used when displaying assets.
	 * 
	 * @param  string  $group
	 * @return Basset_Container
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
		if(!isset($this->assets[$group]) || count($this->assets[$group]) == 0)
		{
			return '';
		}

		$this->cache->register($this->assets, $group, $this->settings['caching']['forget']);

		if($this->cache->has())
		{
			$assets = $this->cache->get();
		}
		else
		{
			$recompile = true;
			$assets = '';

			if(file_exists($compiled = (__DIR__ . DS . 'compiled' . DS . $this->cache->name())))
			{
				// If we are to forget the compiled assets, delete the file and continue.
				if($this->settings['compiling']['forget'])
				{
					@unlink($compiled);
				}
				elseif($this->settings['compiling']['enabled'] && $this->newest($group) < filemtime($compiled))
				{
					// Assets have not been changed since last compile.
					$assets = file_get_contents($compiled);

					// We no longer want the assets recompiled.
					$recompile = false;
				}
			}

			if($recompile)
			{
				foreach($this->arrange($this->assets[$group]) as $name => $data)
				{
					$assets .= $this->asset($name, $group);
				}

				// If compression is enabled then compress the assets according to the group that
				// is being rendered. Compression is done after combining of all files to save on
				// running the compression on each file. This is ensures that the file is
				// compressed before being cached.
				if($this->settings['compression']['enabled'])
				{
					if($group == 'styles')
					{
						$assets = Basset\CSSCompress::process($assets, array('preserve_lines' => $this->settings['compression']['preserve_lines']));
					}
					elseif($group == 'scripts')
					{
						$assets = Basset\JSMin::minify($assets);
					}
				}

				if($this->settings['compiling']['enabled'])
				{
					// Save the recompiled assets.
					file_put_contents($compiled, $assets);
				}
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
		if($this->settings['inline'])
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
		if(!$asset = $this->find($name, $group))
		{
			return PHP_EOL . '/* Basset could not find asset [' . $name . '] */' . PHP_EOL;
		}

		$contents = file_get_contents($asset['file']);

		if($group == 'styles')
		{
			$contents = Basset\URIRewriter::rewrite($contents, dirname($asset['file']));
		}

		if($asset['less'] && $this->settings['less']['php'])
		{
			$less = new Basset\lessc;

			$contents = $less->parse($contents);
		}

		return $contents . PHP_EOL;
	}

	/**
	 * find
	 * 
	 * Attempt to find an asset.
	 * 
	 * @param  string  $name
	 * @param  string  $group
	 * @return mixed
	 */
	protected function find($name, $group)
	{
		$asset = $this->assets[$group][$name];

		if(!parse_url($asset['file'], PHP_URL_SCHEME))
		{
			if(!file_exists($path = ($asset['source'] . DS . $asset['file'])))
			{
				return false;
			}

			$asset['file'] = $asset['source'] . '/' . $asset['file'];
		}
		else
		{
			$asset['external'] = $this->assets[$group][$name]['external'] = true;
		}

		return $asset;
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
			if($asset['updated'] > $newest)
			{
				$newest = $asset['updated'];
			}
		}

		return $newest;
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
		$this->settings['compression']['enabled'] = true;

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
	 * @param  int  $time
	 * @return Basset_Container
	 */
	public function remember($time = -1)
	{
		$this->cache->time = ($time > 0) ? $time : $this->settings['caching']['time'];

		return $this;
	}

	/**
	 * compile
	 * 
	 * Sets Basset to use compiling.
	 * 
	 * @return Basset_Container
	 */
	public function compile()
	{
		$this->settings['compiling']['enabled'] = true;

		return $this;
	}

	/**
	 * forget
	 *
	 * Sets Basset to clear the cache and the compiled assets.
	 *
	 * @return Basset_Container
	 */
	public function forget()
	{
		$this->settings['caching']['forget'] = $this->settings['compiling']['forget'] = true;

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
		if(!$this->group)
		{
			$this->group = (isset($this->assets['styles']) && isset($this->assets['scripts']) ? 'styles' : (isset($this->assets['styles']) ? 'styles' : 'scripts'));
		}

		return $this->render($this->group);
	}

}

/**
 * Basset_Cache
 */
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
	 * @var string $name
	 */
	protected $name;

	/**
	 * @var int $time
	 */
	public $time;

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
	 * Checks if the current group of assets has a cached copy. If the assets are set to be
	 * forgotten the cached copy will not be returned.
	 *
	 * @return bool
	 */
	public function has()
	{
		$name = $this->name();

		if(($has = Cache::has($name)) && $this->forget)
		{
			Cache::forget($name);

			// We don't want to return the cached assets because we cleared
			// the cache and we want a new fresh copy of the assets returned.
			return false;
		}

		return $has;
	}

	/**
	 * get
	 *
	 * Get a cached copy of the group of assets.
	 *
	 * @return mixed
	 */
	public function get()
	{
		if($this->has())
		{
			$assets = Cache::get($name = $this->name());

			return $assets;
		}

		return false;
	}

	/**
	 * run
	 *
	 * Runs the cache and stores it if the cache has not already been set.
	 *
	 * @param  string  $assets
	 */
	public function run($assets)
	{
		if(!$this->has())
		{
			Cache::put($this->name(), $assets, $this->time);
		}
	}

	/**
	 * name
	 *
	 * Determines the cached name of the group of assets.
	 *
	 * @return string
	 */
	public function name()
	{
		if($this->name)
		{
			return $this->name;
		}

		$name = array();

		foreach($this->assets[$this->group] as $asset)
		{
			$name[] = str_replace(path('base'), '', $asset['source']) . '/' . $asset['file'];
		}

		sort($name);

		return $this->name =  md5('basset_' . $this->group . '_' . implode('', $name));
	}
}