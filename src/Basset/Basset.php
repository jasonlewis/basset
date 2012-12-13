<?php namespace Basset;

use Closure;

class Basset {

	/**
	 * Illuminate application instance.
	 * 
	 * @var Illuminate\Foundation\Application
	 */
	protected $app;

	/**
	 * Array of asset collections.
	 * 
	 * @var array
	 */
	protected $collections = array();

	/**
	 * Create a new Basset instance.
	 * 
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function __construct($app)
	{
		$this->app = $app;

		$this->registerCollections();
	}

	/**
	 * Show the assets for a given collection.
	 * 
	 * @param  string  $collection
	 * @return string
	 */
	public function show($collection)
	{
		list($collection, $extension) = explode('.', $collection);

		if ($this->hasCollection($collection))
		{
			$collection = $this->collection($collection);

			$group = ($extension == 'css' ? 'style' : 'script');

			// Determine what course of action will be taken depending on the applications environment.
			// By default if within the production environment Basset will attempt to serve static assets.
			// If, however, Basset is unable to locate the static assets it will default to raw HTML.
			$environment = $this->app['config']->get('basset::production_environment');

			if ($collection->isCompiled($group))
			{
				if ($environment === true or $this->app['env'] == $environment or is_null($environment) and in_array($this->app['env'], array('prod', 'production')))
				{
					$url = $this->app['config']->get('basset::compiling_path').'/'.$collection->getCompiledName($group);

					return new Html($group, $extension, $this->app['url']->asset($url));
				}
			}

			// Spin through each of the assets for the particular group and store the raw HTML response.
			$response = array();

			foreach ($collection->getAssets($group) as $asset)
			{
				$response[] = new Html($asset->getGroup(), $asset->getExtension(), $this->app['url']->asset($asset->getRelativePath()));
			}

			return implode(PHP_EOL, $response);
		}

		return "<!-- Basset could not find collection: {$collection} -->";
	}

	/**
	 * Create a new collection or edit an existing collection instance.
	 * 
	 * @param  string  $name
	 * @param  Closure  $callback
	 * @return Basset\Collection
	 */
	public function collection($name, Closure $callback = null)
	{
		if ( ! isset($this->collections[$name]))
		{
			$this->collections[$name] = new Collection($name, $this->app);
		}

		if (is_callable($callback))
		{
			$callback($this->collections[$name]);
		}

		return $this->collections[$name];
	}

	/**
	 * Add a directory to the array of directories.
	 * 
	 * @param  string  $name
	 * @param  string  $path
	 * @return Basset\Basset
	 */
	public function addDirectory($name, $path)
	{
		$this->app['config']->set("basset::directories.{$name}", $path);

		return $this;
	}

	/**
	 * Alias an asset by giving it a shorter name.
	 * 
	 * @param  string  $name
	 * @param  string  $path
	 * @return Basset\Basset
	 */
	public function aliasAsset($name, $path)
	{
		$this->app['config']->set("basset::assets.{$name}", $path);

		return $this;
	}

	/**
	 * Determine if a collection exists.
	 * 
	 * @param  string  $name
	 * @return bool
	 */
	public function hasCollection($name)
	{
		return isset($this->collections[$name]);
	}

	/**
	 * Get all of the collections.
	 * 
	 * @return array
	 */
	public function getCollections()
	{
		return $this->collections;
	}

	/**
	 * Register collections from the configuration array.
	 * 
	 * @return void
	 */
	public function registerCollections()
	{
		foreach ($this->app['config']->get('basset::collections') as $name => $callback)
		{
			$this->collection($name, $callback);
		}
	}

}