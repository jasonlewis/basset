<?php namespace Basset;

use Closure;
use Illuminate\Config\Repository;
use Basset\Factory\FactoryManager;
use Illuminate\Filesystem\Filesystem;

class Environment {

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
     * Alias of Basset::collection()
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

        // If the collection was given a callable closure where assets can be
        // added we'll fire it now.
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

}