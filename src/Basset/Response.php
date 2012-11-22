<?php namespace Basset;

use Illuminate\Http\Response as HttpResponse;

class Response {

	/**
	 * Illuminate application instance.
	 * 
	 * @var Illuminate\Foundation\Application
	 */
	protected $app;

	/**
	 * Illuminate response instance.
	 * 
	 * @var Illuminate\Http\Response
	 */
	protected $response;

	/**
	 * Create a new response instance.
	 * 
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function __construct($app)
	{
		$this->app = $app;
	}

	/**
	 * Verify that the request is intended for an asset.
	 * 
	 * @return bool
	 */
	public function verifyRequest()
	{
		$handles = $this->app['config']['basset::handles'];

		return str_is("{$handles}/*", trim($this->app['request']->path(), '/'));
	}

	/**
	 * Prepares the response before sending to the browser.
	 * 
	 * @return Illuminate\Http\Response
	 */
	public function prepare()
	{
		$collection = new Collection(null, $this->app);

		$asset = $this->getAssetFromUri($this->app['request']->path());

		if ( ! $asset = $collection->add($asset))
		{
			return;
		}

		// Create a new HttpResponse object with the contents of the asset. Once we have the
		// response object we can adjust the headers before sending it to the browser.
		$this->response = new HttpResponse($collection->compile($asset->getGroup()));

		switch ($asset->getGroup())
		{
			case 'style':
				$this->response->headers->set('content-type', 'text/css');
				break;
			case 'script':
				$this->response->headers->set('content-type', 'application/javascript');
				break;
		}

		return $this;
	}

	/**
	 * Get the response object.
	 * 
	 * @return string
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * Get the asset from the URI.
	 * 
	 * @param  string  $uri
	 * @return string
	 */
	protected function getAssetFromUri($uri)
	{
		$pieces = explode('/', str_replace($this->app['request']->getBaseUrl(), '', $uri));

		unset($pieces[array_search($this->app['config']['basset::handles'], $pieces)]);

		return implode('/', array_filter($pieces));
	}

}