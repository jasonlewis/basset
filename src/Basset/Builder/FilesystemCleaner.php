<?php namespace Basset\Builder;

use Basset\Collection;
use Basset\Environment;
use Basset\Manifest\Repository;
use Illuminate\Filesystem\Filesystem;

class FilesystemCleaner {

    /**
     * Basset environment instance.
     * 
     * @var \Basset\Environment
     */
    protected $environment;

    /**
     * Basset manifest repository instance.
     *
     * @var \Basset\Manifest\Repository
     */
    protected $manifest;

    /**
     * Illuminate filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Path to built collections.
     *
     * @var string
     */
    protected $buildPath;

    /**
     * Create a new build cleaner instance.
     *
     * @param  \Basset\Environment  $environment
     * @param  \Basset\Manifest\Repository  $manifest
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $buildPath
     * @return void
     */
    public function __construct(Environment $environment, Repository $manifest, Filesystem $files, $buildPath)
    {
        $this->environment = $environment;
        $this->manifest = $manifest;
        $this->files = $files;
        $this->buildPath = $buildPath;
    }

    /**
     * Cleans a built collection and the manifest entries.
     *
     * @param  string|\Basset\Collection  $collection
     * @return void
     */
    public function clean($collection = null)
    {
        $collections = is_null($collection) ? $this->environment->getCollections() : array($collection);

        foreach ($collections as $collection)
        {
            if ($this->manifest->has($collection))
            {
                $this->cleanCollectionFiles($collection);
            }
        }
    }

    /**
     * Cleans a built collections files removing any outdated builds.
     * 
     * @param  \Basset\Collection  $collection
     * @return void
     */
    protected function cleanCollectionFiles(Collection $collection)
    {
        $entry = $this->manifest->get($collection);

        foreach ($entry->getProductionFingerprints() as $fingerprint)
        {
            $wildcardPath = $this->replaceFingerprintWithWildcard($fingerprint);

            $this->deleteMatchingFiles($this->buildPath.'/'.$wildcardPath, $fingerprint);
        }

        foreach ($entry->getDevelopmentAssets() as $group => $assets)
        {
            foreach ($assets as $asset)
            {
                $wildcardPath = $this->replaceFingerprintWithWildcard($asset);

                $this->deleteMatchingFiles($this->buildPath.'/'.$collection->getName().'/'.$wildcardPath, $asset);
            }
        }
    }

    /**
     * Delete matching files from the wildcard glob search except the ignored file.
     * 
     * @param  string  $wildcard
     * @param  string  $ignore
     * @return void
     */
    protected function deleteMatchingFiles($wildcard, $ignore)
    {
        foreach ($this->files->glob($wildcard) as $path)
        {
            if (ends_with($path, $ignore)) continue;

            $this->files->delete($path);
        }
    }

    /**
     * Replace a fingerprint with a wildcard.
     * 
     * @param  string  $value
     * @return string
     */
    protected function replaceFingerprintWithWildcard($value)
    {
        return preg_replace('/(.*?)-([\w\d]{32})\.(.*?)/', '$1-*.$3', $value);
    }

    /**
     * Get the manifest repository instance.
     * 
     * @return \Basset\Manifest\Repository
     */
    public function getManifest()
    {
        return $this->manifest;
    }

    /**
     * Get the illuminate filesystem instance.
     * 
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFiles()
    {
        return $this->files;
    }

}