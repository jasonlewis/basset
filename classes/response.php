<?php namespace Basset;

use URI;
use Bundle;
use Laravel\Response as LaravelResponse;

class Response {

	/**
	 * Laravel response instance.
	 * 
	 * @var Laravel\Response
	 */
	protected $response;

	/**
	 * Prepares the response before sending to the browser.
	 * 
	 * @return Laravel\Response
	 */
	public function prepare()
	{
		$collection = new Collection(null);

		$asset = $this->getAssetFromUri(URI::current());

		if ( ! $asset = $collection->add($asset))
		{
			return;
		}

		// Create a new LaravelResponse object with the contents of the asset. Once we have the
		// response object we can adjust the headers before sending it to the browser.
		$this->response = new LaravelResponse($collection->compile($asset->getGroup()));

		switch ($asset->getGroup())
		{
			case 'style':
				$this->response->header('content-type', 'text/css');
				break;
			case 'script':
				$this->response->header('content-type', 'application/javascript');
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
		$pieces = explode('/', $uri);

		unset($pieces[array_search(Bundle::option('basset', 'handles'), $pieces)]);

		return implode('/', array_filter($pieces));
	}

}