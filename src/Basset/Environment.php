<?php namespace Basset;

use Closure;
use ArrayAccess;
use Illuminate\Log\Writer;
use InvalidArgumentException;
use Basset\Factory\AssetFactory;
use Basset\Factory\FilterFactory;

class Environment implements ArrayAccess {

    /**
     * Asset collections.
     *
     * @var array
     */
    protected $collections = array();

    /**
     * Illuminate log writer instance.
     * 
     * @var \Illuminate\Log\Writer
     */
    protected $log;

    /**
     * Basset asset factory instance.
     *
     * @var \Basset\Factory\AssetFactory
     */
    protected $assetFactory;

    /**
     * Basset filter factory instance.
     *
     * @var \Basset\Factory\FilterFactory
     */
    protected $filterFactory;

    /**
     * Asset finder instance.
     *
     * @var \Basset\AssetFinder
     */
    protected $finder;

    /**
     * Create a new environment instance.
     *
     * @param  \Illuminate\Log\Writer  $log
     * @param  \Basset\Factory\AssetFactory  $assetFactory
     * @param  \Basset\Factory\FilterFactory  $filterFactory
     * @param  \Basset\AssetFinder  $finder
     * @return void
     */
    public function __construct(Writer $log, AssetFactory $assetFactory, FilterFactory $filterFactory, AssetFinder $finder)
    {
        $this->log = $log;
        $this->assetFactory = $assetFactory;
        $this->filterFactory = $filterFactory;
        $this->finder = $finder;
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
            $directory = $this->prepareDefaultDirectory();

            $this->collections[$name] = new Collection($name, $directory, $this->filterFactory);
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
     * Prepare the default directory for a new collection.
     * 
     * @return \Basset\Directory
     */
    protected function prepareDefaultDirectory()
    {
        $path = $this->finder->setWorkingDirectory('/');

        return new Directory($this->log, $this->assetFactory, $this->filterFactory, $this->finder, $path);
    }

    /**
     * Get all collections.
     *
     * @return array
     */
    public function all()
    {
        return $this->collections;
    }

    /**
     * Determine if a collection exists.
     *
     * @param  string  $name
     * @return bool
     */
    public function has($name)
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
        return $this->has($offset) ? $this->collection($offset) : null;
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
        return $this->has($offset);
    }

}