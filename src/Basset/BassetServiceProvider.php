<?php namespace Basset;

use Illuminate\Support\ServiceProvider;

define('BASSET_VERSION', '3.0.0');

class BassetServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function register($app)
	{
		// Because Laravel doesn't actually set a public path here we'll define out own. This may become
		// a limitation and hopefully will change at a later date.
		$app['path.public'] = realpath($app['path.base'].'/public');

		$this->registerBindings($app);

		// Register the package configuration with the loader.
		$app['config']->package('jasonlewis/basset', __DIR__.'/../config');

		require_once __DIR__.'/../facades.php';

		// Basset collections can be compiled via Artisan. We need to register the Artisan commands with
		// the console so that commands can be run.
		$this->registerCommands($app);

		// Basset responds to routes for assets that are not within the public directory. This is especially
		// useful when developing an application and static assets are not ideal.
		$this->registerRoutes($app);

		$app['events']->fire('basset.started', array($app['basset']));
	}

	/**
	 * Register the application bindings.
	 * 
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function registerBindings($app)
	{
		$app['basset'] = $app->share(function($app)
		{
			return new Basset($app);
		});

		$app['basset.response'] = $app->share(function($app)
		{
			return new Response($app);
		});
	}

	/**
	 * Register the routes that Basset responds to.
	 * 
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function registerRoutes($app)
	{
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
	public function registerCommands($app)
	{
		$app['command.basset'] = $app->share(function($app)
		{
			return new Console\BassetCommand;
		});

		// The compile and list commands both make use of the compile path, so we'll define
		// it here and use it within the command closures.
		$compilePath = $app['path.base'] . '/' . $app['config']->get('basset::compiling_path');

		$app['command.basset.compile'] = $app->share(function($app) use ($compilePath)
		{
			return new Console\CompileCommand($app, $compilePath);
		});

		$app['command.basset.list'] = $app->share(function($app) use ($compilePath)
		{
			return new Console\ListCommand($app, $compilePath);
		});

		// Listen for the artisan.start even so that the Basset console commands can be resolved.
		$app['events']->listen('artisan.start', function($artisan)
		{
			$artisan->resolveCommands(array(
				'command.basset',
				'command.basset.compile',
				'command.basset.list'
			));
		});
	}

}