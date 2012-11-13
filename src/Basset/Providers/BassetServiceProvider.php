<?php namespace Basset\Providers;

use Basset\Basset;
use Basset\Response;
use Basset\Console\BassetCommand;
use Basset\Console\CompileCommand;
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
		$app['basset'] = $app->share(function($app)
		{
			// Set the application and base paths in the configuration so that they're accessible within Basset.
			$app['config']['path'] = array('app' => $app['path'], 'base' => $app['path.base']);

			// A Basset configuration file can be used to overwrite the default configuration settings. The default
			// settings use some acceptable defaults for most options.
			$app['config']['basset'] = array_merge(require __DIR__.'/../../defaults.php', $app['config']->get('basset', array()));

			return new Basset($app['files'], $app['config'], $app['env']);
		});

		$app['basset.response'] = $app->share(function($app)
		{
			return new Response($app['request'], $app['files'], $app['config']);
		});

		require __DIR__.'/../../facades.php';

		// Basset collections can be compiled via Artisan. We need to register the Artisan commands with
		// the console so that commands can be run.
		$this->registerCommands($app);

		// Basset responds to routes for assets that are not within the public directory. This is especially
		// useful when developing an application and static assets are not ideal.
		$this->registerRoutes($app);

		// Basset is built upon asset collections. Load in the collections file where any collections
		// are defined.
		require __DIR__.'/../../collections.php';

		$app['events']->fire('basset.started', array($app['basset']));
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
			return new BassetCommand;
		});

		$app['command.basset.compile'] = $app->share(function($app)
		{
			$compilePath = $app['path.base'] . '/' . $app['config']['basset.compiling_path'];

			return new CompileCommand($app['basset'], $app['files'], $compilePath);
		});

		// Listen for the Artisan starting event, from here we can resolve the commands related
		// to Basset.
		$app['events']->listen('artisan.start', function($artisan)
		{
			$artisan->resolveCommands(array(
				'command.basset',
				'command.basset.compile'
			));
		});
	}

}