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
        if ($collection instanceof Collection)
        {
            $collection = $collection->getName();
        }

        return isset($this->entries[$collection]);
    }

    /**
     * Get a collection entry from the manifest or create a new entry.
     * 
     * @param  string|\Basset\Collection  $collection
     * @return \Basset\Manifest\Entry
     */
    public function get($collection)
    {
        if ($collection instanceof Collection)
        {
            $collection = $collection->getName();
        }

        return isset($this->entries[$collection]) ? $this->entries[$collection] : $this->entries[$collection] = new Entry;
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