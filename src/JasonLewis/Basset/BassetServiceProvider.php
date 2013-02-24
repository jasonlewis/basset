<?php namespace JasonLewis\Basset;

use Illuminate\Support\ServiceProvider;
use JasonLewis\Basset\Manifest\Repository;
use JasonLewis\Basset\Console\BassetCommand;
use JasonLewis\Basset\Console\CompileCommand;
use JasonLewis\Basset\Compiler\FilesystemCompiler;
use JasonLewis\Basset\Output\Builder as OutputBuilder;
use JasonLewis\Basset\Output\Resolver as OutputResolver;

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
        $this->package('jasonlewis/basset');

        // When booting the application we need to load the collections stored within the manifest
        // repository. These collections indicate the fingerprints required to display the
        // collections correctly.
        $this->app['basset.repository']->load();

        // To process assets dynamically we'll register a route with the router that will allow
        // un-compiled assets to be compiled on the fly and returned to the browser. Static files
        // are not served and it can be much slower.
        $this->registerRoutes();
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

    protected function registerRoutes()
    {
        // To avoid any possibility of duplicating a route we'll store a random string in the session
        // that will be used as the first segment of the URI.
        $randomString = $this->app['session']->get('basset_random_segment', str_random());

        if ( ! $this->app['session']->has('basset_random_segment'))
        {
            $this->app['session']->put('basset_random_segment', $randomString);
        }

        $this->app['router']->get("{$randomString}/{asset}", 'JasonLewis\Basset\Output\Controller@processAsset')
                            ->where('asset', '.*');
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
            $resolver = new OutputResolver($app['basset.repository'], $app['router'], $app['config'], $app['env']);

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
        $this->app['basset.repository'] = $this->app->share(function($app)
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

        $this->app['command.basset.compile'] = $this->app->share(function($app)
        {
            $compiler = new FilesystemCompiler($app['files'], $app['config']);

            // The compile path is where the compiled collections are saved. This path is relative
            // to the public directory, so we'll join the public path and the compile path together.
            $compilePath = $app['path.public'].'/'.$app['config']->get('basset::compiling_path');

            return new CompileCommand($app['basset'], $compiler, $app['basset.repository'], $compilePath);
        });

        // Resolve the commands with Artisan by attaching the event listener to Artisan's
        // startup. This allows us to use the commands from our terminal.
        $this->commands('command.basset', 'command.basset.compile');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('basset', 'basset.repository', 'basset.renderer', 'basset.factory.asset', 'basset.factory.filter');
    }

}