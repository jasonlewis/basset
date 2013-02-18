<?php namespace JasonLewis\Basset;

use Closure;

interface FilterableInterface {

	/**
	 * Apply a filter.
	 * 
	 * @param  string  $filter
	 * @return mixed
	 */
	public function apply($filter);

}