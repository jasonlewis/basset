<?php namespace Basset;

use Closure;
use ArrayAccess;
use InvalidArgumentException;
use Illuminate\Config\Repository;
use Basset\Factory\FactoryManager;
use Illuminate\Filesystem\Filesystem;

class Environment implements ArrayAccess {

    /**
     * Asset collections.
     *
     * @var array
     */
    protected $collections;

    /**
     * Illuminate filesystem instance.
     *
     * @var Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Illuminate config repository instance.
     *
     * @var Illuminate\Config\Repository
     */
    protected $config;

    /**
     * Factory manager instance.
     *
     * @var Basset\Factory\FactoryManager
     */
    protected $factory;

    /**
     * Asset finder instance.
     *
     * @var Basset\AssetFinder
     */
    protected $finder;

    /**
     * Create a new environment instance.
     *
     * @param  Illuminate\Filesystem\Filesystem  $files
     * @param  Illuminate\Config\Repository  $config
     * @param  Basset\Factory\FactoryManager  $factory
     * @param  Basset\AssetFinder  $finder
     * @return void
     */
    public function __construct(Filesystem $files, Repository $config, FactoryManager $factory, AssetFinder $finder)
    {
        $this->files = $files;
        $this->config = $config;
        $this->factory = $factory;
        $this->finder = $finder;
    }

    /**
     * Alias of Basset\Environment::collection()
     *
     * @param  string  $name
     * @param  Closure  $callback
     * @return Basset\Collection
     */
    public function make($name, Closure $callback = null)
    {
        return $this->collection($name, $callback);
    }

    /**
     * Create or return an existing collection.
     *
     * @param  string  $name
     * @param  Closure  $callback
     * @return Basset\Collection
     */
    public function collection($name, Closure $callback = null)
    {
        if ( ! isset($this->collections[$name]))
        {
            $collection = new Collection($name, $this->files, $this->finder, $this->factory);

            $this->collections[$name] = $collection;
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
     * @return Basset\Collection|null
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
     * @var Illuminate\Filesystem\Filesystem
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Get the illuminate config repository instance.
     * 
     * @var Illuminate\Config\Repository
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get the factory manager instance.
     * 
     * @var Basset\Factory\FactoryManager
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * Get the asset finder instance.
     * 
     * @var Basset\AssetFinder
     */
    public function getFinder()
    {
        return $this->finder;
    }

}