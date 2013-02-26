<?php namespace Basset;

use Basset\Manifest\Repository;
use Basset\Console\BuildCommand;
use Basset\Console\CleanCommand;
use Basset\Console\BassetCommand;
use Basset\Builder\FilesystemBuilder;
use Illuminate\Support\ServiceProvider;
use Basset\Output\Builder as OutputBuilder;
use Basset\Output\Resolver as OutputResolver;
use Basset\Output\Controller as OutputController;

define('BASSET_VERSION', '4.0.0');

class BassetServiceProvider extends ServiceProvider {

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
        'Cleaner',
        'OutputBuilder',
        'Factories',
        'Repository',
        'Basset',
        'Commands'
    );

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('jasonlewis/basset', 'basset', __DIR__.'/../');

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
        $me = $this;

        $this->app->booting(function($app) use ($me)
        {
            $hash = $me->getPatternHash();

            $route = $app['router']->get("{$hash}/{collection}/{asset}", 'Basset\Output\Controller@processAsset');

            $route->where('collection', '.*?')->where('asset', '.*');
        });
    }

    /**
     * Register the build cleaner.
     *
     * @return void
     */
    protected function registerCleaner()
    {
        $this->app['basset.cleaner'] = $this->app->share(function($app)
        {
            $buildPath = $app['path.public'].'/'.$app['config']->get('basset::build_path');

            return new BuildCleaner($app['basset.manifest'], $app['files'], $buildPath);
        });
    }

    /**
     * Register the collection output builder.
     *
     * @return void
     */
    protected function registerOutputBuilder()
    {
        $this->app['basset.output'] = $this->app->share(function($app)
        {
            $resolver = new OutputResolver($app['basset.manifest'], $app['router'], $app['config'], $app['env']);

            return new OutputBuilder($resolver, $app['config'], $app['session'], $app['basset']->getCollections());
        });
    }

    /**
     * Register the factories.
     *
     * @return void
     */
    protected function registerFactories()
    {
        $this->app['basset.factory.asset'] = $this->app->share(function($app)
        {
            return new AssetFactory($app['files'], $app['basset.factory.filter'], $app['path.public'], $app['env']);
        });

        $this->app['basset.factory.filter'] = $this->app->share(function($app)
        {
            return new FilterFactory($app['config']);
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
            return new Basset($app['files'], $app['config'], $app['basset.factory.asset'], $app['basset.factory.filter']);
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
            $builder = new FilesystemBuilder($app['files'], $app['config']);

            // The build path is where the built collections are saved. This path is relative
            // to the public directory, so we'll join the public path and the build path together.
            $buildPath = $app['path.public'].'/'.$app['config']->get('basset::build_path');

            return new BuildCommand($app['basset'], $builder, $app['basset.manifest'], $app['basset.cleaner'], $buildPath);
        });

        $this->app['command.basset.clean'] = $this->app->share(function($app)
        {
            return new CleanCommand($app['basset.cleaner']);
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

        $hash = $session->get('basset_hash', str_random());

        if ( ! $session->has('basset_hash'))
        {
            $session->put('basset_hash', $hash);
        }

        return $hash;
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('basset', 'basset.manifest', 'basset.renderer', 'basset.factory.asset', 'basset.factory.filter');
    }

}