<?php namespace Basset;

use Illuminate\Log\Writer;
use Basset\Builder\Builder;
use Basset\Manifest\Manifest;
use Monolog\Handler\NullHandler;
use Basset\Console\BuildCommand;
use Basset\Factory\AssetFactory;
use Basset\Factory\FilterFactory;
use Basset\Console\BassetCommand;
use Monolog\Logger as MonologLogger;
use Basset\Builder\FilesystemCleaner;
use Illuminate\Support\ServiceProvider;

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
        'Logger',
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

        // Tell the logger to use a rotating files setup to log problems encountered during
        // Bassets operation but only when debugging is enabled.
        if ($this->app['config']->get('basset::debug', false))
        {
            $this->app['basset.log']->useDailyFiles($this->app['path.storage'].'/logs/basset.txt', 0, 'warning');
        }

        // If debugging is disabled we'll use a null handler to essentially send all logged
        // messages into a blackhole.
        else
        {
            $handler = new NullHandler(MonologLogger::WARNING);

            $this->app['basset.log']->getMonolog()->pushHandler($handler);
        }

        // Register the build path with the application so that bindings that make use of the
        // build path can easily pull it from the container.
        $this->app->instance('basset.path.build', $this->app['path.public'].'/'.$this->app['config']->get('basset::build_path'));

        // Collections can be defined as an array in the configuration file. We'll register
        // this array of collections with the environment.
        $this->app['basset']->collections($this->app['config']->get('basset::collections'));

        // Load the local manifest that contains the fingerprinted paths to both production
        // and development builds.
        $this->app['basset.manifest']->load();

        $this->registerBladeExtensions();
    }

    /**
     * Register the Blade extensions with the compiler.
     * 
     * @return void
     */
    protected function registerBladeExtensions()
    {
        $blade = $this->app['view']->getEngineResolver()->resolve('blade')->getCompiler();

        $blade->extend(function($value, $compiler)
        {
            $matcher = $compiler->createMatcher('javascripts');
            
            return preg_replace($matcher, '$1<?php echo basset_javascripts$2; ?>', $value);
        });

        $blade->extend(function($value, $compiler)
        {
            $matcher = $compiler->createMatcher('stylesheets');
            
            return preg_replace($matcher, '$1<?php echo basset_stylesheets$2; ?>', $value);
        });

        $blade->extend(function($value, $compiler)
        {
            $matcher = $compiler->createMatcher('assets');
            
            return preg_replace($matcher, '$1<?php echo basset_assets$2; ?>', $value);
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
            return new Server($app);
        });
    }

    /**
     * Register the logger.
     * 
     * @return void
     */
    protected function registerLogger()
    {
        $this->app['basset.log'] = $this->app->share(function($app)
        {
            return new Writer(new \Monolog\Logger('basset'), $app['events']);
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
            return new AssetFactory($app['files'], $app['basset.factory.filter'], $app['basset.log'], $app['env'], $app['path.public']);
        });

        $this->app['basset.factory.filter'] = $this->app->share(function($app)
        {
            $aliases = $app['config']->get('basset::aliases.filters', array());

            $nodePaths = $app['config']->get('basset::node_paths', array());

            return new FilterFactory($app['basset.log'], $aliases, $nodePaths, $app['env']);
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
            return new Environment($app['basset.log'], $app['basset.factory.asset'], $app['basset.factory.filter'], $app['basset.finder']);
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

}