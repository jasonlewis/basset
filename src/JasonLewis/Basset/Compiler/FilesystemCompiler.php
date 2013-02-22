<?php namespace JasonLewis\Basset\Compiler;

use JasonLewis\Basset\Collection;
use JasonLewis\Basset\Exceptions\CompilingNotRequiredException;

class FilesystemCompiler extends StringCompiler {

	/**
	 * Path to output compiled file.
	 * 
	 * @var string
	 */
	protected $outputPath;

	/**
	 * Indicates if the compiling should be forced.
	 * 
	 * @var bool
	 */
	protected $force = false;

	/**
	 * Compile the assets of a collection.
	 * 
	 * @param  JasonLewis\Basset\Collection  $collection
	 * @return string
	 */
	public function compile(Collection $collection, $group)
	{
		$response = parent::compile($collection, $group);

		// If the output path does not exist then we'll attempt to create it.
		if ( ! $this->files->exists($this->outputPath))
		{
			$this->files->makeDirectory($this->outputPath);
		}

		// The fingerprint is an MD5 hash of the compiled response. This allows the cache
		// to be busted when a new lot of assets are compiled.
		$fingerprint = md5($response);

		$extension = $this->determineExtension($group);

		// Before we attempt to save the response to the output file we'll first make sure
		// that a file with the same name does not exist. If one exists then we'll throw
		// an exception unless the compiling is being forced.
		$outputFilePath = "{$this->outputPath}/{$collection->getName()}-{$fingerprint}.{$extension}";

		if ($this->files->exists($outputFilePath) and ! $this->force)
		{
			throw new CompilingNotRequiredException("The [{$group}] on collection [{$collection->getName()}] are up to date.");
		}
		
		$this->files->put($outputFilePath, $response);

		return $outputFilePath;
	}

	/**
	 * Determine the extension based on the group.
	 * 
	 * @param  string  $group
	 * @return string
	 */
	protected function determineExtension($group)
	{
		return $group == 'styles' ? 'css' : 'js';
	}

	/**
	 * Set the output path.
	 * 
	 * @param  string  $path
	 * @return JasonLewis\Basset\Compiler\FilesystemCompiler
	 */
	public function setOutputPath($path)
	{
		$this->outputPath = $path;

		return $this;
	}

	/**
	 * Set the compiling to be forced.
	 * 
	 * @return JasonLewis\Basset\Compiler\FilesystemCompiler
	 */
	public function force()
	{
		$this->force = true;

		return $this;
	}

}