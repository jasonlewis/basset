<?php namespace Basset\Compiler;

use Basset\Collection;
use Basset\Exception\CollectionExistsException;

class FilesystemCompiler extends StringCompiler {

    /**
     * Path to output compiled file.
     *
     * @var string
     */
    protected $compilePath;

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
     * Compile the assets of a collection.
     *
     * @param  Basset\Collection  $collection
     * @return void
     */
    public function compile(Collection $collection, $group)
    {
        $response = parent::compile($collection, $group);

        $response = implode(PHP_EOL, $response);

        // If the compile path does not exist then we'll attempt to create it.
        if ( ! $this->files->exists($this->compilePath))
        {
            $this->files->makeDirectory($this->compilePath);
        }

        // The fingerprint is an MD5 hash of the compiled response. This allows the cache
        // to be busted when a new lot of assets are compiled.
        $this->fingerprint[$group] = md5($response);

        $extension = $collection->determineExtension($group);

        // Before we attempt to save the response to the output file we'll first make sure
        // that a file with the same name does not exist. If one exists then we'll throw
        // an exception unless the compiling is being forced.
        $collectionName = $collection->getName();

        $outputFilePath = "{$this->compilePath}/{$collectionName}-{$this->fingerprint[$group]}.{$extension}";

        if ($this->files->exists($outputFilePath) and ! $this->force)
        {
            throw new CollectionExistsException("The [{$group}] on collection [{$collectionName}] are up to date.");
        }

        $this->files->put($outputFilePath, $response);
    }

    /**
     * Compiles the assets of a collection to individual files for development.
     *
     * @return void
     */
    public function compileDevelopment(Collection $collection, $group)
    {
        $responses = parent::compile($collection, $group);

        $collectionName = $collection->getName();

        // Determine if the compile path exists. If the path does not exist we'll make an attempt
        // to create it. Things could get ugly if we don't.
        $compilePath = "{$this->compilePath}/{$collectionName}";

        if ( ! $this->files->exists($compilePath))
        {
            $this->files->makeDirectory($compilePath);
        }

        $extension = $collection->determineExtension($group);

        // Spin through the responses. For each response we'll need to possibly create it's
        // base directory if it's nested within the compile path. Once we have the directory
        // we can proceed to dump the contents to the file.
        foreach ($responses as $relativePath => $assetContents)
        {
            list($directoryName, $fileName) = array(pathinfo($relativePath, PATHINFO_DIRNAME), pathinfo($relativePath, PATHINFO_FILENAME));

            $filePath = $compilePath;

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
     * Set the compile path.
     *
     * @param  string  $path
     * @return Basset\Compiler\FilesystemCompiler
     */
    public function setCompilePath($path)
    {
        $this->compilePath = $path;

        return $this;
    }

    /**
     * Alias for Basset\Compiler\FilesystemCompiler::setForce(true)
     *
     * @return Basset\Compiler\FilesystemCompiler
     */
    public function force()
    {
        return $this->setForce(true);
    }

    /**
     * Set the compiling to be forced.
     *
     * @param  bool  $force
     * @return Basset\Compiler\FilesystemCompiler
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