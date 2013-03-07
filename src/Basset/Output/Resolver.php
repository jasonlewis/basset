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
     * Resolve a fingerprinted collection.
     *
     * @param  Basset\Collection  $collection
     * @param  string  $group
     * @return string
     */
    public function resolveFingerprintedCollection(Collection $collection, $group)
    {
        $name = $collection->getName();

        if ($this->manifest->hasEntry($name) and $this->runningInProduction() and $this->manifest->getEntry($name)->hasFingerprint($group))
        {
            return $this->manifest->getEntry($name)->getFingerprint($group);
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