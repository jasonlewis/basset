<?php namespace Basset\Factory;

use Basset\Directory;
use Basset\AssetFinder;
use Illuminate\Filesystem\Filesystem;

class DirectoryFactory implements FactoryInterface {

    /**
     * Illuminate filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Basset factory manager instance.
     *
     * @var \Basset\Factory\Manager
     */
    protected $factory;

    /**
     * Basset asset finder instance.
     *
     * @var \Basset\AssetFinder
     */
    protected $finder;

    /**
     * Create a new directory factory instance.
     *
     * @param  Illuminate\Filesystem\Filesystem  $files
     * @param  Basset\Factory\Manager  $factory
     * @return void
     */
    public function __construct(Filesystem $files, Manager $factory, AssetFinder $finder)
    {
        $this->files = $files;
        $this->factory = $factory;
        $this->finder = $finder;
    }

    /**
     * Make a new directory instance.
     *
     * @param  string  $path
     * @return \Basset\Directory
     */
    public function make($path)
    {
        return new Directory($path, $this->files, $this->factory, $this->finder);
    }

}