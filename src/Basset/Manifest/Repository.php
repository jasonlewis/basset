<?php namespace Basset\Manifest;

use Basset\Collection;
use Illuminate\Filesystem\Filesystem;

class Repository {

    /**
     * Illuminate filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Path to the manifest.
     *
     * @var string
     */
    protected $manifestPath;

    /**
     * Manifest entries collection.
     * 
     * @var \Illuminate\Support\Collection
     */
    protected $entries;

    /**
     * Create a new collection repository instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $manifestPath
     * @return void
     */
    public function __construct(Filesystem $files, $manifestPath)
    {
        $this->files = $files;
        $this->manifestPath = $manifestPath;
        $this->entries = new \Illuminate\Support\Collection;
    }

    /**
     * Determine if the manifest has a given collection entry.
     * 
     * @param  string  $collection
     * @return bool
     */
    public function has($collection)
    {
        $collection = $this->getCollectionNameFromInstance($collection);

        return isset($this->entries[$collection]);
    }

    /**
     * Get a collection entry from the manifest or create a new entry.
     * 
     * @param  string|\Basset\Collection  $collection
     * @return null|\Basset\Manifest\Entry
     */
    public function get($collection)
    {
        $collection = $this->getCollectionNameFromInstance($collection);

        return isset($this->entries[$collection]) ? $this->entries[$collection] : null;
    }

    /**
     * Make a collection entry if it does not already exist on the manifest.
     * 
     * @param  string|\Basset\Collection  $collection
     * @return \Basset\Manifest\Entry
     */
    public function make($collection)
    {
        $collection = $this->getCollectionNameFromInstance($collection);

        return $this->has($collection) ? $this->get($collection) : $this->entries[$collection] = new Entry;
    }

    /**
     * Remove a collection from the repository.
     * 
     * @param  string|\Basset\Collection  $collection
     * @return void
     */
    public function remove($collection)
    {
        $collection = $this->getCollectionNameFromInstance($collection);

        if ($this->has($collection)) unset($this->entries[$collection]);
    }

    /**
     * Get all the entries.
     * 
     * @return \Illuminate\Support\Collection
     */
    public function all()
    {
        return $this->entries;
    }

    /**
     * Get the collections name from a collection instance.
     * 
     * @param  string|\Basset\Collection  $collection
     * @return string
     */
    protected function getCollectionNameFromInstance($collection)
    {
        return $collection instanceof Collection ? $collection->getName() : $collection;
    }

    /**
     * Loads and registers the manifest entries.
     *
     * @return void
     */
    public function load()
    {
        $path = $this->manifestPath.'/collections.json';

        if ($this->files->exists($path) and is_array($manifest = json_decode($this->files->get($path), true)))
        {
            foreach ($manifest as $key => $entry)
            {
                $entry = new Entry($entry['fingerprints'], $entry['development']);

                $this->entries->put($key, $entry);
            }
        }
    }

    /**
     * Save the manifest.
     *
     * @return void
     */
    public function save()
    {
        $path = $this->manifestPath.'/collections.json';

        $this->files->put($path, $this->entries->toJson());
    }

}