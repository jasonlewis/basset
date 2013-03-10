<?php namespace Basset;

use Closure;
use FilesystemIterator;
use Basset\Factory\Manager;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Illuminate\Filesystem\Filesystem;
use Basset\Filter\FilterableInterface;

class Directory implements FilterableInterface {

    /**
     * Directory path.
     *
     * @var string
     */
    protected $path;

    /**
     * Illuminate filesystem instance.
     *
     * @var Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Factory manager instance.
     *
     * @var Basset\Factory\Manager
     */
    protected $factory;

    /**
     * Array of assets.
     *
     * @var array
     */
    protected $assets = array();

    /**
     * Array of filters.
     *
     * @var array
     */
    protected $filters = array();

    /**
     * Create a new directory instance.
     *
     * @param  string  $path
     * @param  Illuminate\Filesystem\Filesystem  $files
     * @param  Basset\Factory\Manager  $factory
     * @return void
     */
    public function __construct($path, Filesystem $files, Manager $factory)
    {
        $this->path = $path;
        $this->files = $files;
        $this->factory = $factory;
    }

    /**
     * Recursively iterate through a given path.
     *
     * @param  string  $path
     * @return RecursiveIteratorIterator
     */
    public function recursivelyIterateDirectory($path)
    {
        return new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    }

    /**
     * Iterate through a given path.
     *
     * @param  string  $path
     * @return FilesystemIterator
     */
    public function iterateDirectory($path)
    {
        return new FilesystemIterator($path);
    }

    /**
     * Require the current directory.
     *
     * @return Basset\Directory
     */
    public function requireDirectory()
    {
        foreach ($this->iterateDirectory($this->path) as $file)
        {
            if ($file->isFile())
            {
                $this->assets[] = $this->factory['asset']->make($file->getPathname());
            }
        }

        return $this;
    }

    /**
     * Require the current directory tree.
     *
     * @return Basset\Directory
     */
    public function requireTree()
    {
        foreach ($this->recursivelyIterateDirectory($this->path) as $file)
        {
            if ($file->isFile())
            {
                $this->assets[] = $this->factory['asset']->make($file->getPathname());
            }
        }

        return $this;
    }

    /**
     * Exclude an array of assets.
     *
     * @param  array  $assets
     * @return Basset\Directory
     */
    public function except($assets)
    {
        foreach ($this->assets as $key => $asset)
        {
            if (in_array($asset->getRelativePath(), (array) $assets))
            {
                array_splice($this->assets, $key, 1);
            }
        }

        return $this;
    }

    /**
     * Include only a subset of assets.
     *
     * @param  array  $assets
     * @return Basset\Directory
     */
    public function only($assets)
    {
        foreach ($this->assets as $key => $asset)
        {
            if ( ! in_array($asset->getRelativePath(), (array) $assets))
            {
                array_splice($this->assets, $key, 1);
            }
        }

        return $this;
    }

    /**
     * Get the path to the directory.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get the assets after any directory applied filters are applied to each asset.
     *
     * @return array
     */
    public function getAssets()
    {
        foreach ($this->assets as $key => $asset)
        {
            foreach ($this->filters as $filter)
            {
                $this->assets[$key]->apply($filter);
            }
        }

        $this->filters = array();

        return $this->assets;
    }

    /**
     * Apply a filter to an entire directory.
     *
     * @param  string  $filter
     * @param  Closure  $callback
     * @return Basset\Filter
     */
    public function apply($filter, Closure $callback = null)
    {
        $instance = $this->factory['filter']->make($filter)->setResource($this)->fireCallback($callback);

        return $this->filters[$instance->getFilter()] = $instance;
    }

    /**
     * Get the applied filters.
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Get the factory manager instance.
     * 
     * @return Basset\Factory\Manager
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * Get the illuminate filesystem instance.
     * 
     * @return Illuminate\Filesystem\Filesystem
     */
    public function getFiles()
    {
        return $this->files;
    }

}