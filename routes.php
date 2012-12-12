<?php

/*
|--------------------------------------------------------------------------
| Load Basset On Verified Requests
|--------------------------------------------------------------------------
|
| Hook Basset in to the request cycle by attaching a closure to the before
| event for Basset requests only. This allows Basset to serve non-static
| assets when needed.
|
*/

Route::get('(:bundle)/(:all)', function()
{
	$response = new Basset\Response;

	if ($response->prepare())
	{
		return $response->getResponse();
	}
});