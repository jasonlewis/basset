<?php namespace JasonLewis\Basset;

use Illuminate\Support\ServiceProvider;

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
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('basset');
	}

}