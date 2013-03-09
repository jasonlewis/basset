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
     * Illuminate config repository instance.
     *
     * @var Illuminate\Config\Repository
     */
    protected $config;

    /**
     * Create a new builder instance.
     *
     * @param  Illuminate\Filesystem\Filesystem  $files
     * @param  Illuminate\Config\Repository  $config
     * @return void
     */
    public function __construct(Filesystem $files, Repository $config)
    {
        $this->files = $files;
        $this->config = $config;
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
     * Get the illumiante config repository instance.
     * 
     * @return Illuminate\Config\Repository
     */
    public function getConfig()
    {
        return $this->config;
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