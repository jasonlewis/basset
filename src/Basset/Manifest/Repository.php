<?php namespace Basset\Manifest;

use Basset\Collection;
use Illuminate\Filesystem\Filesystem;

class Repository {

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
     * Manifest instance.
     *
     * @var Basset\Manifest\Manifest
     */
    protected $manifest;

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
        $this->manifest = new Manifest;
    }

    /**
     * Load and set the manifest on the repository instance.
     *
     * @return Basset\Manifest\Manifest
     */
    public function load()
    {
        $manifest = $this->loadManifest();

        if (is_array($manifest))
        {
            foreach ($manifest as $key => $entry)
            {
                $this->manifest->setEntry($key, new Entry($entry));
            }
        }

        return $this->manifest;
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
     * @param  Basset\Collection  $collection
     * @param  array  $fingerprints
     * @param  bool  $development
     * @return void
     */
    public function register(Collection $collection, $fingerprints, $development = false)
    {
        $collectionName = $collection->getName();

        $entry = $this->freshEntry();

        // We can immedietly set the collections fingerprints for the entry if one exists.
        // This fingerprint is used to bust the cache and identify the current compiled file.
        foreach ($fingerprints as $group => $fingerprint)
        {
            $entry->setFingerprint($fingerprint, $group);
        }

        if ($development)
        {
            foreach ($collection->getAssets() as $asset)
            {
                list($relativePath, $absolutePath, $group) = array($asset->getRelativePath(), $asset->getAbsolutePath(), $asset->getGroup());

                // If the asset is remotely hosted then we don't need to get the directory and filename, we can
                // just add the asset to the entry and continue on.
                if ($asset->isRemote())
                {
                    $entry->addDevelopmentAsset($relativePath, $absolutePath, $group);

                    continue;
                }

                $entry->addDevelopmentAsset($relativePath, $asset->getUsablePath(), $group);
            }
        }

        // Make the manifest a variable with the methods scope so that we don't turn the manifest property
        // into a bunch of arrays when writing the manifest.
        $this->manifest->setEntry($collectionName, $entry);

        $this->writeManifest($this->manifest->toJson());
    }

    /**
     * Write to the manifest file.
     *
     * @param  string  $manifest
     * @return array
     */
    public function writeManifest($manifest)
    {
        $path = $this->manifestPath.'/collections.json';

        $this->files->put($path, $manifest);

        return $manifest;
    }

    /**
     * Get the manifest array.
     *
     * @return array
     */
    public function getManifest()
    {
        return $this->manifest;
    }

    /**
     * Get a new fresh manifest entry.
     *
     * @return array
     */
    protected function freshEntry()
    {
        return new Entry;
    }

    /**
     * Dynamically pass method calls to the manifest.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array(array($this->manifest, $method), $parameters);
    }

}