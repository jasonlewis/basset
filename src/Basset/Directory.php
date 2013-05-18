<?php namespace Basset;

use Closure;
use Iterator;
use Exception;
use SplFileInfo;
use FilesystemIterator;
use Illuminate\Log\Writer;
use Basset\Filter\Filterable;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Basset\Factory\AssetFactory;
use Basset\Factory\FilterFactory;
use Basset\Exceptions\AssetNotFoundException;
use Basset\Exceptions\DirectoryNotFoundException;

class Directory extends Filterable {

    /**
     * Directory path.
     *
     * @var string
     */
    protected $path;

    /**
     * Illuminate log writer instance.
     * 
     * @var \Illuminate\Log\Writer
     */
    protected $log;

    /**
     * Basset filter factory instance.
     *
     * @var \Basset\Factory\FilterFactory
     */
    protected $filterFactory;

    /**
     * Basset asset factory instance.
     *
     * @var \Basset\Factory\FilterFactory
     */
    protected $assetFactory;

    /**
     * Basset asset finder instance.
     *
     * @var \Basset\AssetFinder
     */
    protected $finder;

    /**
     * Collection of assets added to the directory.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $assets;

    /**
     * Collection of nested directories.
     * 
     * @var \Illuminate\Support\Collection
     */
    protected $directories;

    /**
     * Create a new directory instance.
     *
     * @param  \Illuminate\Log\Writer  $log
     * @param  \Basset\Factory\AssetFactory  $assetFactory
     * @param  \Basset\Factory\FilterFactory  $filterFactory
     * @param  \Basset\AssetFinder  $finder
     * @param  string  $path
     * @return void
     */
    public function __construct(Writer $log, AssetFactory $assetFactory, FilterFactory $filterFactory, AssetFinder $finder, $path)
    {
        $this->log = $log;
        $this->assetFactory = $assetFactory;
        $this->filterFactory = $filterFactory;
        $this->finder = $finder;
        $this->path = $path;
        $this->assets = new \Illuminate\Support\Collection;
        $this->directories = new \Illuminate\Support\Collection;
        $this->filters = new \Illuminate\Support\Collection;
    }

    /**
     * Find and add an asset to the directory.
     *
     * @param  string  $name
     * @param  \Closure  $callback
     * @return \Basset\Asset
     */
    public function add($name, Closure $callback = null)
    {
        try
        {
            $path = $this->finder->find($name);

            if ( ! isset($this->assets[$path]))
            {
                $asset = $this->assetFactory->make($path);

                $asset->isRemote() and $asset->exclude();

                $this->assets[$path] = $asset;
            }
        }
        catch (AssetNotFoundException $e)
        {
            $this->log->error(sprintf('Asset "%s" could not be found in "%s"', $name, $this->path));

            return $this->assetFactory->make(null);
        }

        if (is_callable($callback))
        {
            call_user_func($callback, $this->assets[$path]);
        }

        return $this->assets[$path];
    }

    /**
     * Find and add a javascript asset to the directory.
     * 
     * @param  string  $name
     * @param  \Closure  $callback
     * @return \Basset\Asset
     */
    public function javascript($name, Closure $callback = null)
    {
        return $this->add($name, function($asset) use ($callback)
        {
            $asset->setGroup('javascripts');

            is_callable($callback) and call_user_func($callback, $asset);
        });
    }

    /**
     * Find and add a stylesheet asset to the directory.
     * 
     * @param  string  $name
     * @param  \Closure  $callback
     * @return \Basset\Asset
     */
    public function stylesheet($name, Closure $callback = null)
    {
        return $this->add($name, function($asset) use ($callback)
        {
            $asset->setGroup('stylesheets');

            is_callable($callback) and call_user_func($callback, $asset);
        });
    }

    /**
     * Change the working directory.
     *
     * @param  string  $path
     * @param  \Closure  $callback
     * @return \Basset\Collection|\Basset\Directory
     */
    public function directory($path, Closure $callback = null)
    {
        try
        {
            $path = $this->finder->setWorkingDirectory($path);

            $this->directories[$path] = new Directory($this->log, $this->assetFactory, $this->filterFactory, $this->finder, $path);

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
            $this->log->error(sprintf('Directory "%s" could not be found in "%s"', $path, $this->path));

            return new Directory($this->log, $this->assetFactory, $this->filterFactory, $this->finder, null);
        }
    }

    /**
     * Recursively iterate through a given path.
     *
     * @param  string  $path
     * @return \RecursiveIteratorIterator
     */
    public function recursivelyIterateDirectory($path)
    {
        try
        {
            return new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        }
        catch (Exception $e) { return false; }
    }

    /**
     * Iterate through a given path.
     *
     * @param  string  $path
     * @return \FilesystemIterator
     */
    public function iterateDirectory($path)
    {
        try
        {
            return new FilesystemIterator($path);
        }
        catch (Exception $e) { return false; }
    }

    /**
     * Require a directory.
     *
     * @param  string  $path
     * @return \Basset\Directory
     */
    public function requireDirectory($path = null)
    {
        if ( ! is_null($path))
        {
            return $this->directory($path)->requireDirectory();
        }

        if ($iterator = $this->iterateDirectory($this->path))
        {
            return $this->processRequire($iterator);
        }

        return $this;
    }

    /**
     * Require a directory tree.
     *
     * @param  string  $path
     * @return \Basset\Directory
     */
    public function requireTree($path = null)
    {
        if ( ! is_null($path))
        {
            return $this->directory($path)->requireTree();
        }

        if ($iterator = $this->recursivelyIterateDirectory($this->path))
        {
            return $this->processRequire($iterator);
        }

        return $this;
    }

    /**
     * Process a require of either the directory or tree.
     * 
     * @param  \Iterator  $iterator
     * @return \Basset\Directory
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
     * @param  \SplFileInfo  $file
     * @return bool
     */
    protected function validAssetFile(SplFileInfo $file)
    {
        return $file->isFile();
    }

    /**
     * Exclude an array of assets.
     *
     * @param  string|array  $assets
     * @return \Basset\Directory
     */
    public function except($assets)
    {
        $assets = array_flatten(func_get_args());

        $this->assets = $this->assets->filter(function($asset) use ($assets)
        {
            return ! in_array($asset->getRelativePath(), $assets);
        });

        return $this;
    }

    /**
     * Include only a subset of assets.
     *
     * @param  string|array  $assets
     * @return \Basset\Directory
     */
    public function only($assets)
    {
        $assets = array_flatten(func_get_args());

        $this->assets = $this->assets->filter(function($asset) use ($assets)
        {
            return in_array($asset->getRelativePath(), $assets);
        });

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
     * @return \Illuminate\Support\Collection
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
     * Get the current directories assets.
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getDirectoryAssets()
    {
        return $this->assets;
    }
    
}