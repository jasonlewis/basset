<?php namespace Basset;

use Closure;
use Iterator;
use SplFileInfo;
use FilesystemIterator;
use Basset\Factory\Manager;
use Basset\Filter\Filterable;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Illuminate\Filesystem\Filesystem;
use Basset\Exception\AssetExistsException;
use Basset\Exception\AssetNotFoundException;
use Basset\Exception\DirectoryNotFoundException;

class Directory extends Filterable {

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
     * Asset collection.
     *
     * @var Illuminate\Support\Collection
     */
    protected $assets;

    /**
     * Directory collection.
     * 
     * @var Illuminate\Support\Collection
     */
    protected $directories;

    /**
     * Asset finder instance.
     *
     * @var Basset\AssetFinder
     */
    protected $finder;

    /**
     * Create a new directory instance.
     *
     * @param  string  $path
     * @param  Illuminate\Filesystem\Filesystem  $files
     * @param  Basset\Factory\Manager  $factory
     * @return void
     */
    public function __construct($path, Filesystem $files, Manager $factory, AssetFinder $finder)
    {
        $this->path = $path;
        $this->files = $files;
        $this->factory = $factory;
        $this->finder = $finder;
        $this->assets = $this->newCollection();
        $this->filters = $this->newCollection();
        $this->directories = $this->newCollection();
    }

    /**
     * Find and add an asset to the directory.
     *
     * @param  string  $name
     * @param  Closure  $callback
     * @return Basset\Asset
     */
    public function add($name, Closure $callback = null)
    {
        try
        {
            $asset = $this->factory['asset']->make($path = $this->finder->find($name));

            $asset->isRemote() and $asset->exclude();

            $this->assets[$path] = $asset;
        }
        catch (AssetNotFoundException $e)
        {
            return $this->factory['asset']->make(null);
        }
        catch (AssetExistsException $e)
        {
            $path = $this->finder->getAssetPath($name);
        }

        if (is_callable($callback))
        {
            call_user_func($callback, $this->assets[$path]);
        }

        return $this->assets[$path];
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
            $path = $this->finder->setWorkingDirectory($path);

            $this->directories[$path] = $this->factory['directory']->make($path);

            // Once we've set the working directory we'll fire the callback so that any added assets
            // are relative to the working directory. After the callback we can revert the working
            // directory.
            is_callable($callback) and call_user_func($callback, $this->directories[$path]);

            $this->finder->resetWorkingDirectory();

            // Once the working directory has been made and reset on the finder we can return and
            // add this directory to the array of directories.
            return $this->directories[$path];
        }
        catch (DirectoryNotFoundException $e)
        {
            return $this->factory['directory']->make(null);
        }
    }

    /**
     * Recursively iterate through a given path.
     *
     * @param  string  $path
     * @return RecursiveIteratorIterator
     */
    public function recursivelyIterateDirectory($path)
    {
        return $this->files->exists($path) ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) : array();
    }

    /**
     * Iterate through a given path.
     *
     * @param  string  $path
     * @return FilesystemIterator
     */
    public function iterateDirectory($path)
    {
        return $this->files->exists($path) ? new FilesystemIterator($path) : array();
    }

    /**
     * Require a directory.
     *
     * @param  string  $path
     * @return Basset\Directory
     */
    public function requireDirectory($path = null)
    {
        if ( ! is_null($path))
        {
            return $this->directory($path)->requireDirectory();
        }

        $iterator = $this->iterateDirectory($this->path);

        return $this->processRequire($iterator);
    }

    /**
     * Require a directory tree.
     *
     * @param  string  $path
     * @return Basset\Directory
     */
    public function requireTree($path = null)
    {
        if ( ! is_null($path))
        {
            return $this->directory($path)->requireDirectory();
        }

        $iterator = $this->recursivelyIterateDirectory($this->path);

        return $this->processRequire($iterator);
    }

    /**
     * Process a require of either the directory or tree.
     * 
     * @param  Iterator  $iterator
     * @return Basset\Directory
     */
    protected function processRequire(Iterator $iterator)
    {
        // Spin through each of the files within the iterator and if their a valid asset they
        // are added to the array of assets for this directory.
        foreach ($iterator as $file)
        {
            if ( ! $this->validAssetFile($file)) continue;

            $path = $file->getPathname();

            $this->add($path);
        }

        return $this;
    }

    /**
     * Determines if the file is a valid asset file.
     * 
     * @param  SplFileInfo  $file
     * @return bool
     */
    protected function validAssetFile(SplFileInfo $file)
    {
        return $file->isFile();
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
     * Get all the assets.
     *
     * @return Illuminate\Support\Collection
     */
    public function getAssets()
    {
        $assets = $this->assets;

        // Spin through each directory and recursively merge the current directories assets
        // on to the directories assets. This maintains the order of adding in the array
        // structure.
        $this->directories->each(function($directory) use (&$assets)
        {
            $assets = $directory->getAssets()->merge($assets);
        });

        // Spin through each of the filters and apply them to each of the assets. Every filter
        // is applied and then later during the build will be removed if it does not apply
        // to a given asset.
        $this->filters->each(function($filter) use (&$assets)
        {
            $assets->each(function($asset) use ($filter) { $asset->apply($filter); });
        });

        return $assets;
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