<?php namespace Basset\Builder;

use Basset\Collection;
use Basset\Exception\CollectionExistsException;

class FilesystemBuilder extends StringBuilder {

    /**
     * Path to output built file.
     *
     * @var string
     */
    protected $buildPath;

    /**
     * Indicates if the compiling should be forced.
     *
     * @var bool
     */
    protected $force = false;

    /**
     * Compiled collection fingerprints.
     *
     * @var array
     */
    protected $fingerprint = array();

    /**
     * Build the assets of a collection.
     *
     * @param  Basset\Collection  $collection
     * @return void
     */
    public function build(Collection $collection, $group)
    {
        $response = array_to_newlines(parent::build($collection, $group));

        if ( ! $this->files->exists($this->buildPath))
        {
            $this->files->makeDirectory($this->buildPath);
        }

        // Create a fingerprint of the built response. The fingerprint is an MD5 hash, this allows
        // the cache to be busted by generating a new hash when the assets are changed.
        $this->fingerprint[$group] = md5($response);

        $extension = $collection->determineExtension($group);

        $collectionName = $collection->getName();

        // If we're not forcefully re-building the collection we'll make sure that an existing
        // collection with the same fingerprint does not already exist. If it does then there's
        // no reason for it to be re-built.
        $outputPath = "{$this->buildPath}/{$collectionName}-{$this->fingerprint[$group]}.{$extension}";

        if ($this->files->exists($outputPath) and ! $this->force)
        {
            throw new CollectionExistsException("The [{$group}] on collection [{$collectionName}] are up to date.");
        }

        $this->files->put($outputPath, $response);
    }

    /**
     * Builds the assets of a collection to individual files for development.
     *
     * @return void
     */
    public function buildDevelopment(Collection $collection, $group)
    {
        $responses = parent::build($collection, $group);

        $collectionName = $collection->getName();

        // Development assets are stored in a sub-directory so as to avoid any possible double-ups
        // between collections.
        $buildPath = "{$this->buildPath}/{$collectionName}";

        if ( ! $this->files->exists($buildPath))
        {
            $this->files->makeDirectory($buildPath);
        }

        $extension = $collection->determineExtension($group);

        // Spin through each of the responses and create the required directories. Each asset that is
        // to be built will then have its contents dumped to a file.
        foreach ($responses as $relativePath => $assetContents)
        {
            list($directoryName, $fileName) = array(pathinfo($relativePath, PATHINFO_DIRNAME), pathinfo($relativePath, PATHINFO_FILENAME));

            $outputPath = $buildPath;

            // If we're not in the base directory of our build path then we'll add the assets directory
            // to the path. We're essentially mimicking the directory structure of the collection.
            if ( ! in_array($directoryName, array('.', '..')))
            {
                $filePath = "{$filePath}/{$directoryName}";
            }

            if ( ! $this->files->exists($filePath))
            {
                $this->files->makeDirectory($filePath);
            }

            $this->files->put("{$filePath}/{$fileName}.{$extension}", $assetContents);
        }
    }

    /**
     * Set the build path.
     *
     * @param  string  $path
     * @return Basset\Builder\FilesystemBuilder
     */
    public function setBuildPath($path)
    {
        $this->buildPath = $path;

        return $this;
    }

    /**
     * Alias for Basset\Builder\FilesystemBuilder::setForce(true)
     *
     * @return Basset\Builder\FilesystemBuilder
     */
    public function force()
    {
        return $this->setForce(true);
    }

    /**
     * Set the compiling to be forced.
     *
     * @param  bool  $force
     * @return Basset\Builder\FilesystemBuilder
     */
    public function setForce($force)
    {
        $this->force = $force;

        return $this;
    }

    /**
     * Get the compiled collections fingerprints.
     *
     * @return string
     */
    public function getFingerprint()
    {
        return $this->fingerprint;
    }

}