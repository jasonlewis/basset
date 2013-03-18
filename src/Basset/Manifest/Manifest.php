<?php namespace Basset\Manifest;

use Basset\Collection;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\ArrayableInterface;

class Manifest implements JsonableInterface, ArrayableInterface {

    /**
     * Array of manifest entries.
     *
     * @var array
     */
    protected $entries = array();

    /**
     * Set an entry on the manifest.
     *
     * @param  string  $key
     * @param  Basset\Manifest\Entry  $entry
     * @return void
     */
    public function setEntry($key, Entry $entry)
    {
        $this->entries[$key] = $entry;
    }

    /**
     * Get an entry from the manifest.
     *
     * @param  string  $key
     * @return Basset\Manifest\Entry|null
     */
    public function getEntry($key)
    {
        return $this->hasEntry($key) ? $this->entries[$key] : null;
    }

    /**
     * Determine if an entry exists in the manifest.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasEntry($key)
    {
        return isset($this->entries[$key]);
    }

    /**
     * Get all the entries from the manifest.
     *
     * @return array
     */
    public function getEntries()
    {
        return $this->entries;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        $entries = array();

        foreach ($this->entries as $key => $entry)
        {
            $entries[$key] = $entry->toArray();
        }

        return $entries;
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

}