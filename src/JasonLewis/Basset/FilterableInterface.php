<?php namespace JasonLewis\Basset;

interface FilterableInterface {

	/**
	 * Apply a filter.
	 * 
	 * @param  string  $filter
	 * @return JasonLewis\Basset\FilterableInterface
	 */
	public function apply($filter);

}