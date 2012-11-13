<?php

use Illuminate\Support\Facade;

class Basset extends Facade {

	/**
	 * Get the registered component.
	 *
	 * @return object
	 */
	protected static function getFacadeAccessor(){ return static::$app['basset']; }

}