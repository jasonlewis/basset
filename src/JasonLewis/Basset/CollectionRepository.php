<?php namespace JasonLewis\Basset;

use Illuminate\Filesystem\Filesystem;

class CollectionRepository {

	/**
	 * Illuminate filesystem instance.
	 * 
	 * @var Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Path to the manifest.
	 * 
	 * @var string
	 */
	protected $manifestPath;

	/**
	 * Manifest array.
	 * 
	 * @var array
	 */
	protected $manifest = array();

	/**
	 * Create a new collection repository instance.
	 * 
	 * @param  Illuminate\Filesystem\Filesystem  $files
	 * @param  string  $manifestPath
	 * @return void
	 */
	public function __construct(Filesystem $files, $manifestPath)
	{
		$this->files = $files;
		$this->manifestPath = $manifestPath;
	}

	/**
	 * Load and set the manifest on the repository instance.
	 * 
	 * @return void
	 */
	public function load()
	{
		$manifest = $this->loadManifest();

		if ( ! is_null($manifest))
		{
			$this->manifest = $manifest;
		}
	}

	/**
	 * Load the manifest from the manifest path.
	 * 
	 * @return string
	 */
	public function loadManifest()
	{
		$path = $this->manifestPath.'/collections.json';

		if ($this->files->exists($path))
		{
			return json_decode($this->files->get($path), true);
		}
	}

	/**
	 * Register a collection with the manifest.
	 * 
	 * @param  JasonLewis\Basset\Collection  $collection
	 * @param  string  $fingerprint
	 * @param  bool  $development
	 * @return void
	 */
	public function register(Collection $collection, $fingerprint, $development = false)
	{
		$entry = $this->find($collection->getName());

		// We can immedietly set the collections fingerprint for the entry if one exists.
		// This fingerprint is used to bust the cache and identify the current compiled file.
		$entry['fingerprint'] = $fingerprint;

		// If the collection has been compiled for development then we'll spin through all
		// the assets within the collection and add their corrosponding development locations
		// so Basset can find them.
		if ($development)
		{
			foreach ($collection->getAssets() as $asset)
			{
				$relativePath = $asset->getRelativePath();

				// We'll get the path info for the relative path to the asset so we can correctly
				// build the path to the development asset.
				$pathInfo = pathinfo($relativePath);

				$entry['development'][$relativePath] = "{$collection->getName()}/{$pathInfo['dirname']}/{$pathInfo['filename']}.{$asset->getValidExtension()}";
			}
		}

		$this->manifest[$collection->getName()] = $entry;

		$this->writeManifest($this->manifest);
	}

	/**
	 * Write to the manifest file.
	 * 
	 * @param  array  $manifest
	 * @return array
	 */
	public function writeManifest($manifest)
	{
		$path = $this->manifestPath.'/collections.json';

		$this->files->put($path, json_encode($manifest));

		return $manifest;
	}

	/**
	 * Find a collection in the manifest or get a fresh entry.
	 * 
	 * @param  string  $name
	 * @return array
	 */
	public function find($name)
	{
		if ( ! isset($this->manifest[$name]))
		{
			return $this->freshEntry();
		}

		return $this->manifest[$name];
	}

	/**
	 * Get a new fresh manifest entry.
	 * 
	 * @return array
	 */
	protected function freshEntry()
	{
		return array('development' => array(), 'fingerprint' => null);
	}

}