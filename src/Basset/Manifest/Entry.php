<?php namespace Basset\Manifest;

use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\ArrayableInterface;

class Entry implements JsonableInterface, ArrayableInterface {

    /**
     * Entry fingerprints.
     *
     * @var array
     */
    protected $fingerprints = array();

    /**
     * Development assets.
     * 
     * @var array
     */
    protected $development = array();

    /**
     * Create a new manifest entry instance.
     *
     * @param  array  $entry
     * @return void
     */
    public function __construct(array $entry = array())
    {
        $this->parseDefaultEntry($entry);
    }

    /**
     * Add a development asset.
     * 
     * @param  string  $originalPath
     * @param  string  $builtPath
     * @param  string  $group
     * @return Basset\Manifest\Entry
     */
    public function addDevelopmentAsset($originalPath, $builtPath, $group)
    {
        $this->development[$group][$originalPath] = $builtPath;

        return $this;
    }

    /**
     * Get development assets.
     * 
     * @param  string  $group
     * @return array
     */
    public function getDevelopmentAssets($group = null)
    {
        if (is_null($group))
        {
            return $this->development;
        }
        elseif (isset($this->development[$group]))
        {
            return $this->development[$group];
        }
    }

    /**
     * Set the entry fingerprint.
     *
     * @param  string  $fingerprint
     * @param  string  $group
     * @return Basset\Manifest\Entry
     */
    public function setFingerprint($fingerprint, $group)
    {
        $this->fingerprints[$group] = $fingerprint;

        return $this;
    }

    /**
     * Determine if entry has a fingerprint.
     *
     * @param  string  $group
     * @return bool
     */
    public function hasFingerprint($group)
    {
        return isset($this->fingerprints[$group]);
    }

    /**
     * Get the entry fingerprint.
     *
     * @param  string  $group
     * @return string|null
     */
    public function getFingerprint($group)
    {
        return $this->hasFingerprint($group) ? $this->fingerprints[$group] : null;
    }

    /**
     * Get all entry fingerprints.
     *
     * @return array
     */
    public function getFingerprints()
    {
        return $this->fingerprints;
    }

    /**
     * Parse the default entry array.
     *
     * @param  array  $entry
     * @return void
     */
    protected function parseDefaultEntry(array $entry)
    {
        foreach (array('fingerprints', 'development') as $key)
        {
            if (isset($entry[$key]))
            {
                $this->$key = $entry[$key];
            }
        }
    }

    /**
     * Convert the entry to it's JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Convert the entry to it's array representation.
     *
     * @return array
     */
    public function toArray()
    {
        return array('fingerprints' => $this->fingerprints, 'development' => $this->development);
    }

}