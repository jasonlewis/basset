<?php namespace JasonLewis\Basset\Output;

use Illuminate\Routing\Router;
use JasonLewis\Basset\Collection;
use JasonLewis\Basset\Compiler\StringCompiler;
use JasonLewis\Basset\Compiler\FilesystemCompiler;
use Illuminate\Config\Repository as ConfigRepository;
use JasonLewis\Basset\Manifest\Repository as ManifestRepository;

class Resolver {

    /**
     * Manfiest repository instance.
     *
     * @var JasonLewis\Basset\Manifest\Repository
     */
    protected $repository;

    /**
     * Illuminate router instance.
     *
     * @var Illuminate\Routing\Router
     */
    protected $router;

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
     * @param  JasonLewis\Basset\Manifest\Repository  $repository
     * @param  Illuminate\Routing\Router  $router
     * @param  Illuminate\Config\Repository  $config
     * @param  string  $appEnvironment
     * @return void
     */
    public function __construct(ManifestRepository $repository, Router $router, ConfigRepository $config, $appEnvironment)
    {
        $this->repository = $repository;
        $this->router = $router;
        $this->config = $config;
        $this->appEnvironment = $appEnvironment;
    }

    /**
     * Resolve a fingerprinted collection.
     *
     * @param  JasonLewis\Basset\Collection  $collection
     * @return string
     */
    public function resolveFingerprintedCollection(Collection $collection, $group)
    {
        $name = $collection->getName();

        if ($this->repository->has($name) and $this->runningInProduction())
        {
            return $this->repository->find($name)->getFingerprint($group);
        }
    }

    /**
     * Resolve a development collection.
     *
     * @param  JasonLewis\Basset\Collection  $collection
     * @return string
     */
    public function resolveDevelopmentCollection(Collection $collection, $group)
    {
        $name = $collection->getName();

        if ($this->repository->has($name))
        {
            return $this->repository->find($name)->getDevelopment($group);
        }
    }

    /**
     * Determine if running in production environment.
     *
     * @return bool
     */
    protected function runningInProduction()
    {
        return $this->appEnvironment == $this->config->get('basset::production');
    }

}