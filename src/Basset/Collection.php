<?php namespace Basset;

use Closure;
use Basset\Factory\Manager;
use Basset\Compiler\StringCompiler;
use Illuminate\Filesystem\Filesystem;
use Basset\Filter\FilterableInterface;
use Basset\Exception\AssetNotFoundException;
use Basset\Exception\DirectoryNotFoundException;

class Collection implements FilterableInterface {

    /**
     * Name of collection.
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
     * Indicates if a collection has been prepared.
     *
     * @var bool
     */
    protected $prepared = false;

    /**
     * Create a new collection instance.
     *
     * @param  string  $name
     * @param  Illuminate\Filesystem\Filesystem  $files
     * @param  Basset\AssetFinder  $finder
     * @param  Basset\Factory\Manager  $factory
     * @return void
     */
    public function __construct($name, Filesystem $files, AssetFinder $finder, Manager $factory)
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
     * Determine an extension based on the group.
     *
     * @param  string  $group
     * @return string
     */
    public function determineExtension($group)
    {
        return str_plural($group) == 'stylesheets' ? 'css' : 'js';
    }

    /**
     * Find and add an asset to the collection.
     *
     * @param  string  $name
     * @param  Closure  $callback
     * @return Basset\Asset
     */
    public function add($name, Closure $callback = null)
    {
        try
        {
            $path = $this->finder->find($name);
        }
        catch (AssetNotFoundException $e)
        {
            return $this->factory['asset']->make(null);
        }

        $asset = $this->factory['asset']->make($path);

        // If an asset is detected as being remotely hosted then by default the asset is to be
        // excluded from the build process. This is to prevent assets being hosted on CDNs
        // being built with a collection.
        $asset->isRemote() and $asset->exclude();

        if (is_callable($callback))
        {
            call_user_func($callback, $asset);
        }

        // If we are within a working directory then the asset is added to the last directory
        // on the stack. Otherwise the asset is added to the collection.
        if ($this->finder->withinWorkingDirectory())
        {
            return $this->directories[$this->finder->getWorkingDirectory()]->add($asset);
        }

        return $this->assets[] = $asset;
    }

    /**
     * Change the working directory.
     *
     * @param  string  $path
     * @param  Closure  $callback
     * @return Basset\Collection|Basset\Directory
     */
    public function directory($path, Closure $callback = null)
    {
        try
        {
            $this->finder->setWorkingDirectory($path);
        }
        catch (DirectoryNotFoundException $e)
        {
            return $this->factory['directory']->make(null);
        }

        $directory = $this->factory['directory']->make($this->finder->getWorkingDirectory());

        $this->directories[$this->finder->getWorkingDirectory()] = $directory;

        // Once we've set the working directory we'll fire the callback so that any added assets
        // are relative to the working directory. After the callback we can revert the working
        // directory.
        is_callable($callback) and call_user_func($callback, $this);

        $this->finder->resetWorkingDirectory();

        // Once the working directory has been made and reset on the finder we can return and
        // add this directory to the array of directories.
        return $directory;
    }

    /**
     * Require a directory.
     *
     * @param  string  $path
     * @return Basset\Collection|Basset\Directory
     */
    public function requireDirectory($path = null)
    {
        return $this->directory($path)->requireDirectory();
    }

    /**
     * Recursively require a directory tree.
     *
     * @param  string  $path
     * @return Basset\Collection|Basset\Directory
     */
    public function requireTree($path = null)
    {
        return $this->directory($path)->requireTree();
    }

    /**
     * Get an array of assets filtered by a group.
     *
     * @param  string  $group
     * @return array
     */
    public function getAssets($group = null)
    {
        $this->prepareCollection();

        // Spin through all the assets and build a new array of assets containing only those
        // belonging to the specific group that might have been applied. We'll also order
        // the assets here based on any positioning that was set when adding.
        $assets = array();

        foreach ($this->assets as $asset)
        {
            if (is_null($group) or $asset->{'is'.ucfirst(str_singular($group))}())
            {
                $key = $asset->getOrder() ?: count($assets) + 1;

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
        $this->prepareCollection();

        // Spin through all the assets and build a new array of assets containing only those
        // belonging to the specific group that might have been applied and only those that
        // have been excluded.
        $assets = array();

        foreach ($this->assets as $asset)
        {
            if ($asset->isExcluded() and (is_null($group) or $asset->{'is'.ucfirst(str_singular($group))}()))
            {
                $key = $asset->getOrder() ?: count($assets) + 1;

                array_splice($assets, $key - 1, 0, array($asset));
            }
        }

        return $assets;
    }

    /**
     * Prepare a collection by merging in directory assets and applying collection wide filters.
     *
     * @return void
     */
    protected function prepareCollection()
    {
        if ($this->prepared)
        {
            return;
        }

        $this->prepared = true;

        // Spin through each of the directories that have been set on the collection and merge
        // their assets with the collections assets.
        foreach ($this->directories as $directory)
        {
            $this->assets = array_merge($this->assets, $directory->getAssets());
        }

        // If there are filters applied to the collection then these filters must be applied tp
        // each asset within the collection. Now that we have all the directory assets we can
        // apply each filter to each of the assets within the collection.
        if ( ! empty($this->filters))
        {
            foreach ($this->assets as $key => $asset)
            {
                foreach ($this->filters as $filter)
                {
                    $this->assets[$key]->apply($filter);
                }
            }

            $this->filters = array();
        }
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
     * Get the added directories.
     * 
     * @return array
     */
    public function getDirectories()
    {
        return $this->directories;
    }

    /**
     * Determine if the collection has been prepared.
     *
     * @return bool
     */
    public function isPrepared()
    {
        return $this->prepared;
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
        $instance = $this->factory['filter']->make($filter);

        $instance->setResource($this)->runCallback($callback);

        return $this->filters[$instance->getFilter()] = $instance;
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

    /**
     * Get the asset finder instance.
     * 
     * @return Basset\AssetFinder
     */
    public function getFinder()
    {
        return $this->finder;
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

}