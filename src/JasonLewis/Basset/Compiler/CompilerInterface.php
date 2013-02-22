<?php namespace JasonLewis\Basset\Compiler;

use JasonLewis\Basset\Collection;

interface CompilerInterface {

	/**
	 * Compile the assets of a collection.
	 * 
	 * @param  JasonLewis\Basset\Collection  $collection
	 * @param  string  $group
	 * @return mixed
	 */
	public function compile(Collection $collection, $group);

}