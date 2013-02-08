<?php namespace JasonLewis\Basset;

use Illuminate\Support\ServiceProvider;

class BassetServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['basset'] = $this->app->share(function($app)
		{
			return new Factory($app['files'], $app['config'], $app['url']);
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