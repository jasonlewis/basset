<?php namespace Basset\Output;

use Basset\Collection;
use Basset\Compiler\StringCompiler;
use Basset\Compiler\FilesystemCompiler;
use Illuminate\Config\Repository as ConfigRepository;
use Basset\Manifest\Repository as ManifestRepository;

class Resolver {

    /**
     * Manfiest repository instance.
     *
     * @var Basset\Manifest\Repository
     */
    protected $manifest;

    /**
     * Illuminate config repository instance.
     *
     * @var Illuminate\Config\Repository
     */
    protected $config;

    /**
     * Application environment.
     *
     * @var string
     */
    protected $appEnvironment;

    /**
     * Resolving collection.
     * 
     * @var Basset\Collection
     */
    protected $collection;

    /**
     * Create a new output resolver instance.
     *
     * @param  Basset\Manifest\Repository  $manifest
     * @param  Illuminate\Config\Repository  $config
     * @param  string  $appEnvironment
     * @return void
     */
    public function __construct(ManifestRepository $manifest, ConfigRepository $config, $appEnvironment)
    {
        $this->manifest = $manifest;
        $this->config = $config;
        $this->appEnvironment = $appEnvironment;
    }

    /**
     * Set the collection to be resolved.
     * 
     * @param  Basset\Collection  $collection
     * @return void
     */
    public function setCollection(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Resolve a fingerprinted collection.
     *
     * @param  string  $group
     * @return string|null
     */
    public function resolveFingerprintedCollection($group)
    {
        $collection = $this->collection->getName();

        if ($this->runningInProduction() and $this->manifest->hasEntry($collection))
        {
            return $this->manifest->getEntry($collection)->getFingerprint($group);
        }
    }

    /**
     * Resolve a development collection.
     *
     * @param  string  $group
     * @return string|null
     */
    public function resolveDevelopmentCollection($group)
    {
        $collection = $this->collection->getName();

        if ($this->manifest->hasEntry($collection))
        {
            return $this->manifest->getEntry($collection)->getDevelopmentAssets($group);
        }
    }

    /**
     * Determine if running in production environment.
     *
     * @return bool
     */
    protected function runningInProduction()
    {
        return in_array($this->appEnvironment, (array) $this->config->get('basset::production', array()));
    }

}