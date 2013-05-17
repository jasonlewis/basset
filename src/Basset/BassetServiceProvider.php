<?php namespace Basset;

use Basset\Factory\Manager;
use Basset\Builder\Builder;
use Basset\Manifest\Manifest;
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
        'Factories',
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
        $this->app['basset']->collections((array) $this->app['config']->get('basset::collections'));

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
            if ($app['basset']->runningInProduction() or $app->runningUnitTests())
            {
                return;
            }
            
            $collections = $app['basset']->all();

            foreach ($collections as $name => $collection)
            {
                try { $app['basset.builder']->buildAsDevelopment($collection, 'stylesheets'); } catch (BuildNotRequiredException $e) {}
                try { $app['basset.builder']->buildAsDevelopment($collection, 'javascripts'); } catch (BuildNotRequiredException $e) {}
            }

            $app['basset.builder.cleaner']->cleanAll();
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
     * Register the asset and filter factories.
     *
     * @return void
     */
    protected function registerFactories()
    {
        $this->app['basset.factory.asset'] = $this->app->share(function($app)
        {
            return new AssetFactory($app['files'], $app['basset.factory.filter'], $app['path.public']);
        });

        $this->app['basset.factory.filter'] = $this->app->share(function($app)
        {
            $aliases = $app['config']->get('basset::aliases.filters', array());

            $nodePaths = $app['config']->get('basset::node_paths', array());

            return new FilterFactory($aliases, $nodePaths, $app['env']);
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

            return new Manifest($app['files'], $meta);
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
            return new Builder($app['files'], $app['basset.manifest'], $app['basset.path.build']);
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
            return new Environment($app['config'], $app['basset.factory.asset'], $app['basset.factory.filter'], $app['basset.finder'], $app['env']);
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
            return new BassetCommand($app['basset.manifest'], $app['basset'], $app['basset.builder.cleaner']);
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
            return new BuildCommand($app['basset'], $app['basset.builder'], $app['basset.builder.cleaner']);
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