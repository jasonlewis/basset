<?php namespace Basset;

use Closure;
use RuntimeException;
use Basset\Factory\FactoryManager;
use Basset\Compiler\StringCompiler;
use Illuminate\Filesystem\Filesystem;
use Basset\Filter\FilterableInterface;
use Basset\Exception\AssetNotFoundException;

class Collection implements FilterableInterface {

    /**
     * Name of the collection.
     *
     * @var string
     */
    protected $name;

    /**
     * Illuminate filesystem instance.
     *
     * @var Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Asset finder instance.
     *
     * @var Basset\AssetFinder
     */
    protected $finder;

    /**
     * Factory manager instance.
     *
     * @var Basset\Factory\FactoryManager
     */
    protected $factory;

    /**
     * Array of assets.
     *
     * @var array
     */
    protected $assets = array();

    /**
     * Array of directories that have been required.
     *
     * @var array
     */
    protected $directories = array();

    /**
     * Array of filters.
     *
     * @var array
     */
    protected $filters = array();

    /**
     * Collection working directory.
     *
     * @var Basset\Directory
     */
    protected $workingDirectory;

    /**
     * Create a new collection instance.
     *
     * @param  string  $name
     * @param  Illuminate\Filesystem\Filesystem  $files
     * @param  Basset\AssetFinder  $finder
     * @param  Basset\Factory\FactoryManager  $factory
     * @return void
     */
    public function __construct($name, Filesystem $files, AssetFinder $finder, FactoryManager $factory)
    {
        $this->name = $name;
        $this->files = $files;
        $this->finder = $finder;
        $this->factory = $factory;
    }

    /**
     * Get the name of the collection.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add an asset to the collection.
     *
     * @param  string  $name
     * @return Basset\Asset
     */
    public function add($name)
    {
        try
        {
            $path = $this->finder->find($name);
        }
        catch (AssetNotFoundException $e)
        {
            return $this->factory->asset->make(null);
        }

        $asset = $this->factory->asset->make($path);

        $asset->isRemote() and $asset->exclude();

        return $this->assets[] = $asset;
    }

    /**
     * Change the working directory.
     *
     * @param  string  $path
     * @param  Closure  $callback
     * @return Basset\Collection
     */
    public function directory($path, Closure $callback)
    {
        $this->finder->setWorkingDirectory($path);

        // Once we've set the working directory we'll fire the callback so that any added assets
        // are relative to the working directory. After the callback we can revert the working
        // directory.
        call_user_func($callback, $this);

        $this->finder->setWorkingDirectory(null);

        return $this;
    }

    /**
     * Process the collection by retrieving all assets for each directory and then applying
     * any collection filters to every asset.
     *
     * @return void
     */
    public function processCollection()
    {
        foreach ($this->directories as $directory)
        {
            $directory->processFilters();

            $this->assets = array_merge($this->assets, $directory->getAssets());
        }

        // If there are filters applied to the collection then these filters must be applied tp
        // each asset within the collection. Spin through all the assets and apply the filter!
        if ( ! empty($this->filters))
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
    }

    /**
     * Apply a filter to the entire collection.
     *
     * @param  string  $filter
     * @param  Closure  $callback
     * @return Basset\Filter
     */
    public function apply($filter, Closure $callback = null)
    {
        $filter = $this->factory->filter->make($filter, $callback, $this);

        $key = $filter->getFilter();

        return $this->filters[$key] = $filter;
    }

    /**
     * Get an array of assets filtered by a group.
     *
     * @param  string  $group
     * @return array
     */
    public function getAssets($group = null)
    {
        $assets = array();

        foreach ($this->assets as $asset)
        {
            if (is_null($group) or $asset->{'is'.ucfirst(str_singular($group))}())
            {
                $key = $asset->getPosition() ?: count($assets) + 1;

                array_splice($assets, $key - 1, 0, array($asset));
            }
        }

        return $assets;
    }

    /**
     * Get an array of excluded assets filtered by a group.
     *
     * @param  string  $group
     * @return array
     */
    public function getExcludedAssets($group = null)
    {
        $assets = array();

        foreach ($this->assets as $asset)
        {
            if ($asset->isExcluded() and (is_null($group) or $asset->{'is'.ucfirst(str_singular($group))}()))
            {
                $key = $asset->getPosition() ?: count($assets) + 1;

                array_splice($assets, $key - 1, 0, array($asset));
            }
        }

        return $assets;
    }

    /**
     * Determine the extension based on the group.
     *
     * @param  string  $group
     * @return string
     */
    public function determineExtension($group)
    {
        return str_plural($group) == 'styles' ? 'css' : 'js';
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

    public function __call($method, $parameters)
    {
        if (starts_with($method, 'require'))
        {
            // If no path has been supplied then we'll use the working directory and require it
            // or its tree, depending on what was called.
            if (empty($parameters))
            {
                $path = $this->finder->getWorkingDirectory();

                $directory = new Directory($path, $this->files, $this->factory);

                return $this->directories[] = $directory->{$method}();
            }

            return $this->directory($parameters[0], function($directory) use ($method)
            {
                $directory->{$method}();
            });

        }
    }

}