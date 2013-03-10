<?php namespace Basset\Builder;

use Basset\Collection;
use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;

abstract class Builder implements BuilderInterface {

    /**
     * Illuminate filesystem instance.
     *
     * @var Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new builder instance.
     *
     * @param  Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Build the javascripts of a collection.
     *
     * @param  Basset\Collection  $collection
     * @return mixed
     */
    public function buildJavascripts(Collection $collection)
    {
        return $this->build($collection, 'javascripts');
    }

    /**
     * Build the stylesheets of a collection.
     *
     * @param  Basset\Collection  $collection
     * @return mixed
     */
    public function buildStylesheets(Collection $collection)
    {
        return $this->build($collection, 'stylesheets');
    }

    /**
     * Get the illumiante filesystem instance.
     * 
     * @return Illuminate\Filesystem\Filesystem
     */
    public function getFiles()
    {
        return $this->files;
    }

}