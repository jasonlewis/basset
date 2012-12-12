<?php namespace Basset\Facades;

use IoC;
use BadMethodCallException;

class Facade {

	/**
	 * Provide a terser static interface for the registered instances.
	 * 
	 * @param  string  $method
	 * @param  array  $paramters
	 * @return mixed
	 */
	public static function __callStatic($method, $parameters)
	{
		$instance = IoC::resolve(static::getAccessor());

		if (method_exists($instance, $method))
		{
			return call_user_func_array(array($instance, $method), $parameters);
		}

		throw new BadMethodCallException('Could not find method ['.$method.'] on ['.static::getAccessor().']');
	}

}