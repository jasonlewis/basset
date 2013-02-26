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
     * @return array
     */
    public function load()
    {
        $manifest = $this->loadManifest();

        if ( ! is_null($manifest))
        {
            $this->manifest = $this->parseManifest($manifest);
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

        // If the collection has been compiled for development then we'll spin through all
        // the assets within the collection and add their corrosponding development locations
        // so Basset can find them.
        if ($development)
        {
            foreach ($collection->getAssets() as $asset)
            {
                $path = $asset->getRelativePath();

                $group = $asset->getGroup();

                if ($asset->isRemote())
                {
                    $entry->addDevelopment($path, $path, $group);

                    continue;
                }

                // We'll get the path info for the relative path to the asset so we can correctly
                // build the path to the development asset.
                list($directoryName, $fileName) = array(pathinfo($path, PATHINFO_DIRNAME), pathinfo($path, PATHINFO_FILENAME));

                $extension = $asset->getUsableExtension();

                $entry->addDevelopment($path, "{$directoryName}/{$fileName}.{$extension}", $group);
            }
        }

        $this->manifest[$collectionName] = $entry->toArray();

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
     * Parse a manifest array.
     *
     * @param  array  $manifest
     * @return array
     */
    public function parseManifest(array $manifest)
    {
        foreach ($manifest as $key => $entry)
        {
            $manifest[$key] = new Entry($entry);
        }

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
     * Determine if a collection exists in the manifest.
     *
     * @param  string  $name
     * @return bool
     */
    public function has($name)
    {
        return $this->find($name) !== $this->freshEntry();
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

}