<?php namespace JasonLewis\Basset;

use Closure;

interface FilterableInterface {

	/**
	 * Apply a filter.
	 * 
	 * @param  string  $filter
	 * @param  Closure  $callback
	 * @return mixed
	 */
	public function apply($filter, Closure $callback = null);

	/**
	 * Get the applied filters.
	 * 
	 * @return array
	 */
	public function getFilters();

}