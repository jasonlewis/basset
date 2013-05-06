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
     * Apply gzip to built collections.
     *
     * @var bool
     */
    protected $gzip;

    /**
     * Build the assets of a collection.
     *
     * @param  Basset\Collection  $collection
     * @param  string  $group
     * @param  bool  $development
     * @return void
     */
    public function build(Collection $collection, $group, $development = false)
    {
        $responses = parent::build($collection, $group);

        if ( ! $this->files->exists($this->buildPath))
        {
            $this->files->makeDirectory($this->buildPath);
        }

        // If the development switch was given as true then we'll pass the collection and built
        // responses along and continue to build each of the assets for the development
        // environment.
        if ($development)
        {
            return $this->buildAsDevelopment($collection, $group, $responses);
        }

        $responses = array_to_newlines($responses);

        // Create a fingerprint of the built response. The fingerprint is an MD5 hash, this allows
        // the cache to be busted by generating a new hash when the assets are changed.
        $this->fingerprint[$group] = md5($responses);

        // If we're not forcefully re-building the collection we'll make sure that an existing
        // collection with the same fingerprint does not already exist. If it does then there's
        // no reason for it to be re-built.
        $outputPath = "{$this->buildPath}/{$collection->getName()}-{$this->fingerprint[$group]}.{$collection->getExtension($group)}";

        if ($this->files->exists($outputPath) and ! $this->force)
        {
            throw new CollectionExistsException("The [{$group}] on collection [{$collection->getName()}] are up to date.");
        }

        $this->files->put($outputPath, $this->gzip($responses));
    }


    /**
     * Builds the assets of a collection as individual files for development.
     *
     * @param  Basset\Collection  $collection
     * @param  string  $group
     * @param  array  $responses
     * @return void
     */
    public function buildAsDevelopment(Collection $collection, $group, $responses)
    {
        // Development assets are stored in a sub-directory so as to avoid any possible file name
        // duplicates that exist within different collections.
        $buildPath = "{$this->buildPath}/{$collection->getName()}";

        ! $this->files->exists($buildPath) and $this->files->makeDirectory($buildPath);

        $fileExtension = $collection->getExtension($group);

        // Spin through each of the responses and create the required directories. Each asset that is
        // to be built will then have its contents dumped to a file.
        foreach ($responses as $relativePath => $assetContents)
        {
            list($directoryName, $fileName) = array(pathinfo($relativePath, PATHINFO_DIRNAME), pathinfo($relativePath, PATHINFO_FILENAME));

            // If we're not in the base directory of our build path then we'll add the assets directory
            // to the path. We're essentially mimicking the directory structure of the collection.
            $outputPath = $buildPath;

            if ( ! in_array($directoryName, array('.', '..')))
            {
                $outputPath = "{$outputPath}/{$directoryName}";
            }

            ! $this->files->exists($outputPath) and $this->files->makeDirectory($outputPath, 0777, true);

            $this->files->put("{$outputPath}/{$fileName}.{$fileExtension}", $this->gzip($assetContents));
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
     * Set built collections to be Gzipped.
     * 
     * @param  bool  $gzip
     * @return Basset\Builder\FilesystemBuilder
     */
    public function setGzip($gzip)
    {
        $this->gzip = $gzip;

        return $this;
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
     * Set the building to be forced.
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
     * Get the built collections fingerprints.
     *
     * @return string
     */
    public function getFingerprint()
    {
        return $this->fingerprint;
    }

}