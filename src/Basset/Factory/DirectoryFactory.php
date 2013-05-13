<?php namespace Basset\Factory;

use Basset\Directory;
use Basset\AssetFinder;
use Illuminate\Filesystem\Filesystem;

class DirectoryFactory implements FactoryInterface {

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
     * @param  \Basset\Factory\Manager  $factory
     * @param  \Basset\AssetFinder  $finder
     * @return void
     */
    public function __construct(Manager $factory, AssetFinder $finder)
    {
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
        return new Directory($path, $this->factory, $this->finder);
    }

}