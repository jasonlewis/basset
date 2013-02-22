<?php namespace JasonLewis\Basset;

use Illuminate\Support\ServiceProvider;
use JasonLewis\Basset\Console\BassetCommand;
use JasonLewis\Basset\Console\CompileCommand;
use JasonLewis\Basset\Compiler\FilesystemCompiler;

define('BASSET_VERSION', '4.0.0');

class BassetServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('jasonlewis/basset');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['basset.manager'] = $this->app->share(function($app)
		{
			return new AssetManager($app['files'], $app['path.public'], $app['env']);
		});

		$this->app['basset'] = $this->app->share(function($app)
		{
			return new Factory($app['files'], $app['config'], $app['url'], $app['basset.manager']);
		});

		$this->registerCommands();
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

			// The output path is where the compiled collections are saved. This path is relative
			// to the public directory.
			$outputPath = $app['path.public'].'/'.$app['config']->get('basset::compiling_path');

			return new CompileCommand($app['basset'], $compiler, $outputPath);
		});

		$this->commands('command.basset', 'command.basset.compile');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('basset', 'basset.manager');
	}

}