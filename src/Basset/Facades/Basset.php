<?php namespace Basset\Facades;

use Illuminate\Support\Facades\Facade;

class Basset extends Facade {

	/**
	 * Get the registered component.
	 *
	 * @return object
	 */
	protected static function getFacadeAccessor(){ return 'basset'; }

}