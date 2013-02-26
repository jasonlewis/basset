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
     * Build the scripts of a collection.
     *
     * @param  Basset\Collection  $collection
     * @return mixed
     */
    public function buildScripts(Collection $collection)
    {
        return $this->build($collection, 'scripts');
    }

    /**
     * Build the styles of a collection.
     *
     * @param  Basset\Collection  $collection
     * @return mixed
     */
    public function buildStyles(Collection $collection)
    {
        return $this->build($collection, 'styles');
    }

}