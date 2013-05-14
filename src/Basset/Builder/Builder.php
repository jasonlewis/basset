<?php namespace Basset\Builder;

use Basset\Collection;
use Basset\Manifest\Repository;
use Illuminate\Filesystem\Filesystem;
use Basset\Exceptions\BuildNotRequiredException;

class Builder {

    /**
     * Illuminate filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Basset manifest repository instance.
     * 
     * @var \Basset\Manifest\Repository
     */
    protected $manifest;

    /**
     * Basset filesystem cleaner instance.
     * 
     * @var \Basset\Builder\FilesystemCleaner
     */
    protected $cleaner;

    /**
     * Path to built collections.
     * 
     * @var string
     */
    protected $buildPath;

    /**
     * Indicates if the build will be pre-gzipped.
     * 
     * @var bool
     */
    protected $gzip = false;

    /**
     * Indicates if the build will be forced.
     * 
     * @var bool
     */
    protected $force = false;

    /**
     * Create a new builder instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Basset\Manifest\Repository  $manifest
     * @param  \Basset\Builder\FilesystemCleaner  $cleaner
     * @param  string  $buildPath
     * @return void
     */
    public function __construct(Filesystem $files, Repository $manifest, FilesystemCleaner $cleaner, $buildPath)
    {
        $this->files = $files;
        $this->manifest = $manifest;
        $this->cleaner = $cleaner;
        $this->buildPath = $buildPath;

        $this->makeBuildPath();
    }

    /**
     * Build a production collection.
     * 
     * @param  \Basset\Collection  $collection
     * @param  string  $group
     * @return void
     * @throws \Basset\Exceptions\BuildNotRequiredException
     */
    public function buildAsProduction(Collection $collection, $group)
    {
        // Get the assets of the given group from the collection. The collection is also responsible
        // for handling any ordering of the assets so that we just need to build them.
        $assets = $collection->getAssetsWithoutExcluded($group);

        $entry = $this->manifest->make($name = $collection->getName());

        // Build the assets and transform the array into a newline separated string. We'll use this
        // as a basis for the collections fingerprint and it will decide as to whether the
        // collection needs to be rebuilt.
        $build = array_to_newlines($assets->map(function($asset) { return $asset->build(); })->all());

        $fingerprint = $collection->getName().'-'.md5($build).'.'.$collection->getExtension($group);

        $path = $this->buildPath.'/'.$fingerprint;

        // If the build is empty or we're not forcing the build and the collection has already been
        // built or the collection itself has not changed then we'll throw an exception as there
        // is no point in rebuilding the collection.
        if (empty($build) or ($fingerprint == $entry->getProductionFingerprint($group) and ! $this->force and $this->files->exists($path)))
        {
            throw new BuildNotRequiredException;
        }
        
        $this->files->put($path, $build);

        $entry->setProductionFingerprint($group, $fingerprint);

        return $this->processAfterBuild($collection);
    }

    /**
     * Build a development collection.
     * 
     * @param  \Basset\Collection  $collection
     * @param  string  $group
     * @return void
     * @throws \Basset\Exceptions\BuildNotRequiredException
     */
    public function buildAsDevelopment(Collection $collection, $group)
    {
        // Get the assets of the given group from the collection. The collection is also responsible
        // for handling any ordering of the assets so that we just need to build them.
        $assets = $collection->getAssetsWithoutExcluded($group);

        $entry = $this->manifest->make($name = $collection->getName());

        // If there are no changes to the collection then we'll instead look at each asset individually
        // for any possible changes. If the asset is not in the collections manifest entry or the
        // fingerprint on the asset does match the manifest fingerprint then the asset will
        // be rebuilt.
        if ( ! $this->collectionHasChanged($assets, $entry, $group) and ! $this->force)
        {
            $assets = $assets->filter(function($asset) use ($entry)
            {
                return ! $entry->hasDevelopmentAsset($asset) or $asset->getFingerprintedPath() != $entry->getDevelopmentAsset($asset);
            });
        }

        // Otherwise if there are changes to the actual collection itself we'll reset the development
        // assets on the entry so we can add the assets in in the new order they may be defined.
        else
        {
            $entry->resetDevelopmentAssets($group);
        }

        if ( ! $assets->isEmpty())
        {
            foreach ($assets as $asset)
            {
                $path = "{$this->buildPath}/{$name}/{$asset->getFingerprintedPath()}";

                // If the build directory does not exist we'll attempt to recursively create it so we can
                // build the asset to the directory.
                ! $this->files->exists($directory = dirname($path)) and $this->files->makeDirectory($directory, 0777, true);

                $this->files->put($path, $this->gzip($asset->build()));

                // Add the development asset to the manifest entry so that we can save the built asset
                // to the manifest.
                $entry->addDevelopmentAsset($asset);
            }

            $this->processAfterBuild($collection);
        }
        else
        {
            throw new BuildNotRequiredException;
        }
    }

    /**
     * Process the collection after a build.
     * 
     * @param  \Basset\Collection  $collection
     * @return void
     */
    protected function processAfterBuild(Collection $collection)
    {
        $this->manifest->save();

        $this->cleaner->clean($collection);
    }

    /**
     * Determine if the collections assets have changed.
     * 
     * @param  \Illuminate\Support\Collection  $assets
     * @param  \Basset\Manifest\Entry  $entry
     * @param  string  $group
     * @return bool
     */
    protected function collectionHasChanged($assets, $entry, $group)
    {
        // If the manifest entry doesn't even have the group registered then it's obvious that the
        // collection has changed and needs to be rebuilt.
        if ( ! $entry->hasDevelopmentAssets($group))
        {
            return true;
        }

        $manifest = $entry->getDevelopmentAssets($group);

        // With no group the entire manifest will be flattened using the relative asset paths as values.
        if (is_null($group))
        {
            $manifest = array_flatten(array_map(function($group){ return array_keys($group); }, $manifest));
        }

        // The same applies here except we'll be using a given group on the manifest.
        else
        {
            $manifest = array_flatten(array_keys($manifest));
        }

        // Compute the difference between the collections assets and the manifests assets. If we get
        // an array of values then the collection has changed since the last build and everything
        // should be rebuilt.
        $difference = array_diff_assoc($manifest, $assets->map(function($asset) { return $asset->getRelativePath(); })->flatten());

        return ! empty($difference);
    }

    /**
     * Make the build path if it does not exist.
     * 
     * @return void
     */
    protected function makeBuildPath()
    {
        if ( ! $this->files->exists($this->buildPath))
        {
            $this->files->makeDirectory($this->buildPath);
        }
    }

    /**
     * If Gzipping is enabled the the zlib extension is loaded we'll Gzip the contents
     * with a maximum compression level of 9.
     * 
     * @param  string  $contents
     * @return string
     */
    protected function gzip($contents)
    {
        if ($this->gzip and function_exists('gzencode'))
        {
            return gzencode($contents, 9);
        }

        return $contents;
    }

    /**
     * Set built collections to be gzipped.
     * 
     * @param  bool  $gzip
     * @return \Basset\Builder\Builder
     */
    public function setGzip($gzip)
    {
        $this->gzip = $gzip;

        return $this;
    }

    /**
     * Set the building to be forced.
     *
     * @param  bool  $force
     * @return \Basset\Builder\Builder
     */
    public function setForce($force)
    {
        $this->force = $force;

        return $this;
    }

    /**
     * Get the illumiante filesystem instance.
     * 
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Get the basset build cleaner instance.
     * 
     * @return \Basset\Builder\FilesystemCleaner
     */
    public function getCleaner()
    {
        return $this->cleaner;
    }

    /**
     * Get the basset manifest repository instance.
     * 
     * @return \Basset\Manifest\Repository
     */
    public function getManifest()
    {
        return $this->manifest;
    }

}