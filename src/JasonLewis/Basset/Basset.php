<?php namespace JasonLewis\Basset;

use Closure;
use Illuminate\Config\Repository;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Filesystem\Filesystem;

class Basset {

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
     * Illuminate url generator instance.
     *
     * @var Illuminate\Routing\UrlGenerator
     */
    protected $url;

    /**
     * Basset asset factory instance.
     *
     * @var JasonLewis\Basset\AssetFactory
     */
    protected $assetFactory;

    /**
     * Basset filter factory instance.
     *
     * @var JasonLewis\Basset\FilterFactory
     */
    protected $filterFactory;

    /**
     * Create a new factory instance.
     *
     * @param  Illuminate\Filesystem\Filesystem  $files
     * @param  Illuminate\Config\Repository  $config
     * @param  JasonLewis\Basset\AssetFactory  $assetFactory
     * @param  JasonLewis\Basset\FilterFactory  $filterFactory
     * @return void
     */
    public function __construct(Filesystem $files, Repository $config, AssetFactory $assetFactory, FilterFactory $filterFactory)
    {
        $this->files = $files;
        $this->config = $config;
        $this->assetFactory = $assetFactory;
        $this->filterFactory = $filterFactory;
    }

    /**
     * Alias of Basset::collection()
     *
     * @param  string  $name
     * @param  Closure  $callback
     * @return JasonLewis\Basset\Collection
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
     * @return JasonLewis\Basset\Collection
     */
    public function collection($name, Closure $callback = null)
    {
        if ( ! isset($this->collections[$name]))
        {
            $collection = new Collection($this->files, $this->config, $name);

            // Set the asset and filter factories on the collection. These are used to find and
            // make assets and filters.
            $collection->setAssetFactory($this->assetFactory);

            $collection->setFilterFactory($this->filterFactory);

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