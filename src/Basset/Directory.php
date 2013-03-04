<?php namespace Basset;

use Closure;
use FilesystemIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Illuminate\Filesystem\Filesystem;

class Directory implements FilterableInterface {

    /**
     * Path to the directory.
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
     * Basset asset factory instance.
     *
     * @var Basset\AssetFactory
     */
    protected $assetFactory;

    /**
     * Basset filter factory instance.
     *
     * @var Basset\FilterFactory
     */
    protected $filterFactory;

    /**
     * Array of assets.
     *
     * @var array
     */
    protected $assets;

    /**
     * Array of filters.
     *
     * @var array
     */
    protected $filters;

    /**
     * Create a new directory instance.
     *
     * @param  string  $path
     * @param  Illuminate\Filesystem\Filesystem  $files
     * @param  Basset\AssetFactory  $assetFactory
     * @param  Basset\FilterFactory  $filterFactory
     * @return void
     */
    public function __construct($path, Filesystem $files, AssetFactory $assetFactory, FilterFactory $filterFactory)
    {
        $this->files = $files;
        $this->assetFactory = $assetFactory;
        $this->filterFactory = $filterFactory;
        $this->path = $path;
    }

    /**
     * Recursively iterate through the directory.
     *
     * @param  string  $path
     * @return RecursiveIteratorIterator
     */
    public function recursivelyIterateDirectory($path)
    {
        return new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    }

    /**
     * Iterate through the directory.
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
                $this->assets[] = $this->assetFactory->make($file->getPathname());
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
                $this->assets[] = $this->assetFactory->make($file->getPathname());
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
                unset($this->assets[$key]);
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
                unset($this->assets[$key]);
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
     * Get the array of assets.
     *
     * @return array
     */
    public function getAssets()
    {
        return $this->assets;
    }

    /**
     * Process the filters by applying each filter to every asset.
     *
     * @return void
     */
    public function processFilters()
    {
        foreach ($this->assets as $key => $asset)
        {
            foreach ($this->filters as $filter)
            {
                $this->assets[$key]->apply($filter);
            }
        }

        // After applying all the filters to all the assets we'll reset the filters array.
        $this->filters = array();
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
        $filter = $this->filterFactory->make($filter, $callback, $this);

        $key = $filter->getFilter();

        return $this->filters[$key] = $filter;
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

}