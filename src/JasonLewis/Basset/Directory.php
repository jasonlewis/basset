<?php namespace JasonLewis\Basset;

use FilesystemIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class Directory {

	/**
	 * Path to the directory.
	 * 
	 * @var string
	 */
	protected $path;

	/**
	 * Create a new directory instance.
	 * 
	 * @param  string  $path
	 * @return void
	 */
	public function __construct($path)
	{
		$this->path = $path;
	}

	/**
	 * Recursively iterate through the directory.
	 * 
	 * @return RecursiveIteratorIterator
	 */
	public function recursivelyIterateDirectory()
	{
		return new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->path));
	}

	/**
	 * Iterate through the directory.
	 * 
	 * @return FilesystemIterator
	 */
	public function iterateDirectory()
	{
		return new FilesystemIterator($this->path);	
	}

	/**
	 * Get the path to the directory.
	 * 
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

}