<?php namespace Basset;

use Illuminate\Support\ServiceProvider;

define('BASSET_VERSION', '3.0.0');

class BassetServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		// Register the package configuration with the loader.
		$this->app['config']->package('jasonlewis/basset', __DIR__.'/../config');

		// Because Laravel doesn't actually set a public path here we'll define out own. This may become
		// a limitation and hopefully will change at a later date.
		$this->app['path.public'] = realpath($this->app['path.base'].'/'.$this->app['config']->get('basset::public'));

		$this->registerBindings();

		$this->registerCommands();
	}

	/**
	 * Boot the service provider.
	 * 
	 * @return void
	 */
	public function boot()
	{
		$this->registerRoutes();
	}

	/**
	 * Register the application bindings.
	 * 
	 * @return void
	 */
	public function registerBindings()
	{
		$this->app['basset'] = $this->app->share(function($app)
		{
			return new Basset($app);
		});

		$this->app['basset.response'] = $this->app->share(function($app)
		{
			return new Response($app);
		});
	}

	/**
	 * Register the routes that Basset responds to.
	 * 
	 * @return void
	 */
	public function registerRoutes()
	{
		$app = $this->app;

		$app['router']->before(function($request) use ($app)
		{
			if ($app['basset.response']->verifyRequest() and $app['basset.response']->prepare())
			{
				return $app['basset.response']->getResponse();
			}
		});		
	}

	/**
	 * Register the artisan commands.
	 * 
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function registerCommands()
	{
		$this->app['command.basset'] = $this->app->share(function($app)
		{
			return new Console\BassetCommand;
		});

		// The compile and list commands both make use of the compile path, so we'll define
		// it here and use it within the command closures.
		$compilePath = $this->app['path.base'] . '/' . $this->app['config']->get('basset::compiling_path');

		$this->app['command.basset.compile'] = $this->app->share(function($app) use ($compilePath)
		{
			return new Console\CompileCommand($app, $compilePath);
		});

		$this->app['command.basset.list'] = $this->app->share(function($app) use ($compilePath)
		{
			return new Console\ListCommand($app, $compilePath);
		});

		$this->commands('command.basset', 'command.basset.compile', 'command.basset.list');
	}

}