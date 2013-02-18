<?php namespace JasonLewis\Basset;

class Asset implements FilterableInterface {

	/**
	 * Path to the asset.
	 * 
	 * @var string
	 */
	protected $path;

	/**
	 * Create a new asset instance.
	 * 
	 * @param  string  $path
	 * @return void
	 */
	public function __construct($path)
	{
		$this->path = $path;
	}

	public function apply($filter)
	{

	}

}