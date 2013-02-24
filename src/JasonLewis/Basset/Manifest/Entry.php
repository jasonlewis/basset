<?php namespace JasonLewis\Basset\Manifest;

use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\ArrayableInterface;

class Entry implements JsonableInterface, ArrayableInterface {

    /**
     * Entry development assets.
     *
     * @var array
     */
    protected $development = array();

    /**
     * Entry fingerprint.
     *
     * @var array
     */
    protected $fingerprint = array();

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
     * Set the entry fingerprint.
     *
     * @param  string  $fingerprint
     * @param  string  $group
     * @return JasonLewis\Basset\Manifest\Entry
     */
    public function setFingerprint($fingerprint, $group)
    {
        $this->fingerprint[$group] = $fingerprint;

        return $this;
    }

    /**
     * Add a development asset path.
     *
     * @param  string  $path
     * @return JasonLewis\Basset\Manifest\Entry
     */
    public function addDevelopment($originalPath, $compiledPath, $group)
    {
        if ( ! isset($this->development[$group]) or ! array_key_exists($originalPath, $this->development))
        {
            $this->development[$group][$originalPath] = $compiledPath;
        }

        return $this;
    }

    /**
     * Get the entry fingerprint.
     *
     * @param  string  $group
     * @return string
     */
    public function getFingerprint($group)
    {
        return $this->fingerprint[$group];
    }

    /**
     * Get the development assets.
     *
     * @param  string  $group
     * @return array
     */
    public function getDevelopment($group = null)
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
     * Determine if entry is fingerprtined.
     *
     * @param  string  $group
     * @return bool
     */
    public function isFingerprinted($group)
    {
        return ! is_null($this->fingerprint[$group]);
    }

    /**
     * Parse the default entry array.
     *
     * @param  array  $entry
     * @return void
     */
    protected function parseDefaultEntry(array $entry)
    {
        if (isset($entry['development']))
        {
            $this->development = $entry['development'];
        }

        if (isset($entry['fingerprint']))
        {
            $this->fingerprint = $entry['fingerprint'];
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
        return array('development' => $this->development, 'fingerprint' => $this->fingerprint);
    }

}