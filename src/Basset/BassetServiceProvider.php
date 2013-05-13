<?php namespace Basset;

use Basset\Factory\Manager;
use Basset\Builder\Builder;
use Basset\Manifest\Repository;
use Basset\Console\BuildCommand;
use Basset\Console\CleanCommand;
use Basset\Factory\AssetFactory;
use Basset\Factory\FilterFactory;
use Basset\Console\BassetCommand;
use Basset\Factory\DirectoryFactory;
use Basset\Builder\FilesystemCleaner;
use Illuminate\Support\ServiceProvider;
use Basset\Exceptions\BuildNotRequiredException;

class BassetServiceProvider extends ServiceProvider {

    /**
     * Basset version.
     *
     * @var string
     */
    const VERSION = '4.0.0';

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
        'AssetFinder',
        'FactoryManager',
        'Server',
        'Manifest',
        'Builder',
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

        $this->app['basset.path.build'] = $this->app['path.public'].'/'.$this->app['config']['basset::build_path'];

        // Register the collections defined in the configuration. By default an "application"
        // collection is provided with a clean installation of Basset.
        $collections = $this->app['config']->get('basset::collections', array());

        $this->app['basset']->collections($collections);

        // When booting the application we need to load the collections stored within the manifest
        // repository. These collections indicate the fingerprints required to display the
        // collections correctly.
        $this->app['basset.manifest']->load();

        $this->buildOutstandingCollections();
    }

    /**
     * Register a global "before" filter to build any outstanding development collections.
     * 
     * @return void
     */
    public function buildOutstandingCollections()
    {
        $app = $this->app;

        $this->app->before(function() use ($app)
        {
            if ( ! $app['basset']->runningInProduction() and ! $app->runningUnitTests())
            {
                $collections = $app['basset']->getCollections();

                foreach ($collections as $collection)
                {
                    try
                    {
                        $app['basset.builder']->buildAsDevelopment($collection, 'stylesheets');
                    }
                    catch (BuildNotRequiredException $exception) {}
                }
            }
        });
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
            $this->{'register'.$component}();
        }
    }

    /**
     * Register the asset finder.
     * 
     * @return void
     */
    protected function registerAssetFinder()
    {
        $this->app['basset.finder'] = $this->app->share(function($app)
        {
            return new AssetFinder($app['files'], $app['config'], $app['path.public']);
        });
    }

    /**
     * Register the collection server.
     *
     * @return void
     */
    protected function registerServer()
    {
        $this->app['basset.server'] = $this->app->share(function($app)
        {
            return new Server($app['basset'], $app['basset.manifest'], $app['config'], $app['url']);
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

            $factory['filter'] = new FilterFactory($app['config']);

            $factory['asset'] = new AssetFactory($app['files'], $factory, $app['path.public'], $app['env']);

            $factory['directory'] = new DirectoryFactory($factory, $app['basset.finder']);

            return $factory;
        });
    }

    /**
     * Register the collection repository.
     *
     * @return void
     */
    protected function registerManifest()
    {
        $this->app['basset.manifest'] = $this->app->share(function($app)
        {
            $meta = $app['config']->get('app.manifest');

            return new Repository($app['files'], $meta);
        });
    }

    /**
     * Register the collection builder.
     *
     * @return void
     */
    protected function registerBuilder()
    {
        $this->app['basset.builder'] = $this->app->share(function($app)
        {
            return new Builder($app['files'], $app['basset.manifest'], $app['basset.builder.cleaner'], $app['basset.path.build']);
        });

        $this->app['basset.builder.cleaner'] = $this->app->share(function($app)
        {
            return new FilesystemCleaner($app['basset'], $app['basset.manifest'], $app['files'], $app['basset.path.build']);
        });
    }

    /**
     * Register the basset environment.
     *
     * @return void
     */
    protected function registerBasset()
    {
        $this->app['basset'] = $this->app->share(function($app)
        {
            return new Environment($app['files'], $app['config'], $app['basset.factory'], $app['basset.finder'], $app['env']);
        });
    }

    /**
     * Register the commands.
     *
     * @return void
     */
    public function registerCommands()
    {
        $this->registerBassetCommand();
        
        $this->registerBuildCommand();

        $this->commands('command.basset', 'command.basset.build');
    }

    /**
     * Register the basset command.
     * 
     * @return void
     */
    protected function registerBassetCommand()
    {
        $this->app['command.basset'] = $this->app->share(function($app)
        {
            $meta = $app['config']->get('app.manifest');

            return new BassetCommand($app['files'], $app['basset.builder.cleaner'], $meta);
        });
    }

    /**
     * Register the build command.
     * 
     * @return void
     */
    protected function registerBuildCommand()
    {
        $this->app['command.basset.build'] = $this->app->share(function($app)
        {
            return new BuildCommand($app['basset'], $app['basset.builder']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('basset', 'basset.manifest', 'basset.server', 'basset.factory', 'basset.builder', 'basset.builder.cleaner', 'basset.finder');
    }

}