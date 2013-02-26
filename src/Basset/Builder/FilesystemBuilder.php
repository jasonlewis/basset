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
        $response = parent::build($collection, $group);

        $response = implode(PHP_EOL, $response);

        // If the build path does not exist then we'll attempt to create it.
        if ( ! $this->files->exists($this->buildPath))
        {
            $this->files->makeDirectory($this->buildPath);
        }

        // The fingerprint is an MD5 hash of the built response. This allows the cache
        // to be busted when a new lot of assets are built.
        $this->fingerprint[$group] = md5($response);

        $extension = $collection->determineExtension($group);

        // Before we attempt to save the response to the output file we'll first make sure
        // that a file with the same name does not exist. If one exists then we'll throw
        // an exception unless the building is being forced.
        $collectionName = $collection->getName();

        $outputFilePath = "{$this->buildPath}/{$collectionName}-{$this->fingerprint[$group]}.{$extension}";

        if ($this->files->exists($outputFilePath) and ! $this->force)
        {
            throw new CollectionExistsException("The [{$group}] on collection [{$collectionName}] are up to date.");
        }

        $this->files->put($outputFilePath, $response);
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

        // Determine if the build path exists. If the path does not exist we'll make an attempt
        // to create it. Things could get ugly if we don't.
        $buildPath = "{$this->buildPath}/{$collectionName}";

        if ( ! $this->files->exists($buildPath))
        {
            $this->files->makeDirectory($buildPath);
        }

        $extension = $collection->determineExtension($group);

        // Spin through the responses. For each response we'll need to possibly create it's
        // base directory if it's nested within the build path. Once we have the directory
        // we can proceed to dump the contents to the file.
        foreach ($responses as $relativePath => $assetContents)
        {
            list($directoryName, $fileName) = array(pathinfo($relativePath, PATHINFO_DIRNAME), pathinfo($relativePath, PATHINFO_FILENAME));

            $filePath = $buildPath;

            // If the directory name where the file is located is not one of the dot directories
            // then we'll add the directory to the path as well.
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