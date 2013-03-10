<?php namespace Basset;

use Basset\Factory\Manager;
use Basset\Manifest\Repository;
use Basset\Console\BuildCommand;
use Basset\Console\CleanCommand;
use Basset\Factory\AssetFactory;
use Basset\Factory\FilterFactory;
use Basset\Console\BassetCommand;
use Basset\Factory\DirectoryFactory;
use Basset\Builder\FilesystemBuilder;
use Illuminate\Support\ServiceProvider;
use Basset\Output\Server as OutputServer;
use Basset\Output\Resolver as OutputResolver;
use Basset\Output\Controller as OutputController;

class BassetServiceProvider extends ServiceProvider {

    /**
     * Basset version.
     *
     * @var string
     */
    const VERSION = '4.0.0';

    /**
     * Name of the session hash.
     * 
     * @var string
     */
    const SESSION_HASH = 'basset_hash';

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Components to register on the provider.
     *
     * @var array
     */
    protected $components = array(
        'FactoryManager',
        'OutputServer',
        'Repository',
        'Commands',
        'Basset'
    );

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('jasonlewis/basset', 'basset', __DIR__.'/../');

        // Register the collections defined in the configuration. By default an "application"
        // collection is provided with a clean installation of Basset.
        $this->app['basset']->registerCollections($this->app['config']->get('basset::collections', array()));

        // When booting the application we need to load the collections stored within the manifest
        // repository. These collections indicate the fingerprints required to display the
        // collections correctly.
        $this->app['basset.manifest']->load();

        // To process assets dynamically we'll register a route with the router that will allow
        // assets to be built on the fly and returned to the browser. Static files will not be served
        // meaning this is much slower then building assets via the command line.
        $this->registerRouting();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        foreach ($this->components as $component)
        {
            $this->{"register{$component}"}();
        }
    }

    /**
     * Register the asset processing route with the router.
     *
     * @return void
     */
    protected function registerRouting()
    {
        // Bind the output controller to the container so that we can resolve it's dependencies.
        // This is essential in making the controller testable and more robust.
        $this->app['Basset\Output\Controller'] = function($app)
        {
            return new OutputController($app['basset']);
        };

        // We can now register a callback to the booting event fired by the application. This
        // allows us to hook in after the sessions have been started and properly register the
        // route with the router.
        $provider = $this;

        $this->app->booting(function($app) use ($provider)
        {
            $route = $app['router']->get("{$provider->getPatternHash()}/{collection}/{asset}", 'Basset\Output\Controller@processAsset');

            $route->where('asset', '.*');
        });
    }

    /**
     * Register the collection output server.
     *
     * @return void
     */
    protected function registerOutputServer()
    {
        $this->app['basset.output'] = $this->app->share(function($app)
        {
            $resolver = new OutputResolver($app['basset.manifest'], $app['config'], $app['env']);

            return new OutputServer($resolver, $app['config'], $app['session'], $app['url'], $app['basset']->getCollections());
        });
    }

    /**
     * Register the factory manager.
     *
     * @return void
     */
    protected function registerFactoryManager()
    {
        $this->app['basset.factory'] = $this->app->share(function($app)
        {
            $factory = new Manager;

            // Register the filter, asset, and directory factories with the factory manager so that other
            // classes don't have multiple dependencies for the factories.
            $factory['filter'] = new FilterFactory($app['config']);

            $factory['asset'] = new AssetFactory($app['files'], $factory, $app['path.public'], $app['env']);

            $factory['directory'] = new DirectoryFactory($app['files'], $factory);

            return $factory;
        });
    }

    /**
     * Register the collection repository.
     *
     * @return void
     */
    protected function registerRepository()
    {
        $this->app['basset.manifest'] = $this->app->share(function($app)
        {
            return new Repository($app['files'], $app['config']->get('app.manifest'));
        });
    }

    /**
     * Register basset.
     *
     * @return void
     */
    protected function registerBasset()
    {
        $this->app['basset'] = $this->app->share(function($app)
        {
            $finder = new AssetFinder($app['files'], $app['config'], $app['path.public']);

            return new Environment($app['files'], $app['config'], $app['basset.factory'], $finder);
        });
    }

    /**
     * Register the commands.
     *
     * @return void
     */
    public function registerCommands()
    {
        $this->app['command.basset'] = $this->app->share(function($app)
        {
            return new BassetCommand;
        });

        $this->app['command.basset.build'] = $this->app->share(function($app)
        {
            $buildPath = $app['path.public'].'/'.$app['config']->get('basset::build_path');

            $builder = new FilesystemBuilder($app['files']);

            $cleaner = new BuildCleaner($app['basset.manifest'], $app['files'], $buildPath);

            return new BuildCommand($app['basset'], $builder, $app['basset.manifest'], $cleaner, $buildPath);
        });

        $this->app['command.basset.clean'] = $this->app->share(function($app)
        {
            $buildPath = $app['path.public'].'/'.$app['config']->get('basset::build_path');

            $cleaner = new BuildCleaner($app['basset.manifest'], $app['files'], $buildPath);

            return new CleanCommand($cleaner);
        });

        // Resolve the commands with Artisan by attaching the event listener to Artisan's
        // startup. This allows us to use the commands from our terminal.
        $this->commands('command.basset', 'command.basset.build', 'command.basset.clean');
    }

    /**
     * Generate a random hash for the URI pattern.
     *
     * @return string
     */
    public function getPatternHash()
    {
        $session = $this->app['session'];

        // Get the hash from the session. If the hash does not exist then we'll generate a random
        // string and store that in the session.
        $hash = $session->get(static::SESSION_HASH, str_random());

        ! $session->has(static::SESSION_HASH) and $session->put(static::SESSION_HASH, $hash);

        return $hash;
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('basset', 'basset.manifest', 'basset.output', 'basset.factory.asset', 'basset.factory.filter');
    }

}