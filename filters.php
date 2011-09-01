<?php use Basset\Basset as Basset;
return array(

	/**
	 * This before filter should not be altered. This method will set Basset to combine files
	 * by default regardless of what you have specified in the config.
	 */
	'before' => function()
	{
		Basset::routed();
	},

	/**
	 * If you append a file extension such as .css or .js to the end of your routes then it will
	 * automatically set the content type instead of manually specifying an after filter.
	 */
	'after' => function($response, $method, $uri)
	{
		$types = array(
			'css' 	=> 'text/css',
			'js'	=> 'text/javascript'
		);
		
		if(strrpos($uri, '.') !== false && ($ext = \File::extension($uri))) $response->header('Content-Type', $types[$ext]);
	},

	/**
	 * Manual CSS after filter.
	 */
	'css' => function($response)
	{
		$response->header('Content-Type', 'text/css');
	},

	/**
	 * Manual JS after filter.
	 */
	'js' => function($response)
	{
		$response->header('Content-Type', 'text/javascript');
	}

);