<?php namespace Basset\Compiler;

use Basset\Collection;
use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;

abstract class Compiler implements CompilerInterface {

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
     * Create a new compiler instance.
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
     * Compile the scripts of a collection.
     *
     * @param  Basset\Collection  $collection
     * @return mixed
     */
    public function compileScripts(Collection $collection)
    {
        return $this->compile($collection, 'scripts');
    }

    /**
     * Compile the styles of a collection.
     *
     * @param  Basset\Collection  $collection
     * @return mixed
     */
    public function compileStyles(Collection $collection)
    {
        return $this->compile($collection, 'styles');
    }

}