<?php namespace Basset;

use Closure;
use ArrayAccess;
use Basset\Factory\Manager;
use InvalidArgumentException;
use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;

class Environment implements ArrayAccess {

    /**
     * Asset collections.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $collections;

    /**
     * Illuminate filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Illuminate config repository instance.
     *
     * @var \Illuminate\Config\Repository
     */
    protected $config;

    /**
     * Factory manager instance.
     *
     * @var \Basset\Factory\Manager
     */
    protected $factory;

    /**
     * Asset finder instance.
     *
     * @var \Basset\AssetFinder
     */
    protected $finder;

    /**
     * Application working environment.
     * 
     * @var string
     */
    protected $applicationEnvironment;

    /**
     * Create a new environment instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Config\Repository  $config
     * @param  \Basset\Factory\Manager  $factory
     * @param  \Basset\AssetFinder  $finder
     * @param  string  $applicationEnvironment
     * @return void
     */
    public function __construct(Filesystem $files, Repository $config, Manager $factory, AssetFinder $finder, $applicationEnvironment)
    {
        $this->files = $files;
        $this->config = $config;
        $this->factory = $factory;
        $this->finder = $finder;
        $this->applicationEnvironment = $applicationEnvironment;
        $this->collections = new \Illuminate\Support\Collection;
    }

    /**
     * Alias of \Basset\Environment::collection()
     *
     * @param  string  $name
     * @param  \Closure  $callback
     * @return \Basset\Collection
     */
    public function make($name, Closure $callback = null)
    {
        return $this->collection($name, $callback);
    }

    /**
     * Create or return an existing collection.
     *
     * @param  string  $name
     * @param  \Closure  $callback
     * @return \Basset\Collection
     */
    public function collection($name, Closure $callback = null)
    {
        if ( ! isset($this->collections[$name]))
        {
            $this->collections[$name] = new Collection($name, $this->finder, $this->factory);;
        }

        // If the collection has been given a callable closure then we'll execute the closure with
        // the collection instance being the only parameter given. This allows users to begin
        // using the collection instance to add assets.
        if (is_callable($callback))
        {
            call_user_func($callback, $this->collections[$name]);
        }

        return $this->collections[$name];
    }

    /**
     * Get all collections.
     *
     * @return array
     */
    public function getCollections()
    {
        return $this->collections;
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
     * Register a package with the environment.
     * 
     * @param  string  $package
     * @param  string  $namespace
     * @return void
     */
    public function package($package, $namespace = null)
    {
        if (is_null($namespace))
        {
            list($vendor, $namespace) = explode('/', $package);
        }

        $this->finder->addNamespace($namespace, $package);
    }

    /**
     * Register an array of collections.
     * 
     * @param  array  $collections
     * @return void
     */
    public function collections(array $collections)
    {
        foreach ($collections as $name => $callback)
        {
            $this->make($name, $callback);
        }
    }

    /**
     * Determine if running in production environment.
     *
     * @return bool
     */
    public function runningInProduction()
    {
        return in_array($this->applicationEnvironment, (array) $this->config->get('basset::production', array()));
    }

    /**
     * Set a collection offset.
     *
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset))
        {
            throw new InvalidArgumentException("No collection name given.");
        }

        $this->collection($offset, $value);
    }

    /**
     * Get a collection offset.
     *
     * @param  string  $offset
     * @return null|\Basset\Collection
     */
    public function offsetGet($offset)
    {
        return $this->hasCollection($offset) ? $this->collection($offset) : null;
    }

    /**
     * Unset a collection offset.
     *
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->collections[$offset]);
    }

    /**
     * Determine if a collection offset exists.
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->hasCollection($offset);
    }

    /**
     * Get the illuminate filesystem instance.
     * 
     * @var \Illuminate\Filesystem\Filesystem
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Get the illuminate config repository instance.
     * 
     * @var \Illuminate\Config\Repository
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get the basset factory manager instance.
     * 
     * @var \Basset\Factory\Manager
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * Get the basset asset finder instance.
     * 
     * @var \Basset\AssetFinder
     */
    public function getFinder()
    {
        return $this->finder;
    }

}