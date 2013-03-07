<?php namespace Basset;

use Closure;
use Basset\Factory\FactoryManager;
use Basset\Compiler\StringCompiler;
use Illuminate\Filesystem\Filesystem;
use Basset\Filter\FilterableInterface;
use Basset\Exception\AssetNotFoundException;
use Basset\Exception\DirectoryNotFoundException;

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
        try
        {
            $this->finder->setWorkingDirectory($path);
        }
        catch (DirectoryNotFoundException $e)
        {
            return $this->factory['directory']->make(null);
        }

        // Once we've set the working directory we'll fire the callback so that any added assets
        // are relative to the working directory. After the callback we can revert the working
        // directory.
        $response = call_user_func($callback, $this);

        $this->finder->resetWorkingDirectory();

        // If we received a response from the callback then we'll return the response as it may
        // be a directory instance that users can apply filters to.
        return $response ?: $this;
    }

    /**
     * Require a directory.
     *
     * @param  string  $path
     * @return Basset\Collection|Basset\Directory
     */
    public function requireDirectory($path = null)
    {
        return $this->processRequire('directory', $path);
    }

    /**
     * Recursively require a directory tree.
     *
     * @param  string  $path
     * @return Basset\Collection|Basset\Directory
     */
    public function requireTree($path = null)
    {
        return $this->processRequire('tree', $path);
    }

    /**
     * Process a directory require.
     *
     * @param  string  $method
     * @param  string  $path
     * @return Basset\Collection|Basset\Directory
     */
    protected function processRequire($method, $path)
    {
        $method = ucfirst($method);

        // If a path has been supplied to the require then we'll change to work within that directory.
        // Once we're working within the directory we can require the tree or the directory again
        // and it'll perform the correct actions on the working directory.
        if ( ! is_null($path))
        {
            return $this->directory($path, function($directory) use ($method)
            {
                return $collection->{"require{$method}"}();
            });
        }

        // Now that no path has been supplied we can safely assume that we're working within the
        // original directory that was given. We'll now make the directory with the factory and
        // then perform the original require request that was given.
        $directory = $this->factory['directory']->make($this->finder->getWorkingDirectory());

        return $this->directories[] = $directory->{"require{$method}"}();
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
        $instance = $this->factory->filter->make($filter, $callback, $this);

        return $this->filters[$instance->getFilter()] = $instance;
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
     * Get the applied filters.
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

}