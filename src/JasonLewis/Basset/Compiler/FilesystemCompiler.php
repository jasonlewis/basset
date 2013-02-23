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

		// The response will be an array. We'll implode the items joining them with a new
		// line.
		$response = implode(PHP_EOL, $response);

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
	 * Compiles the assets of a collection to individual files for development.
	 * 
	 * @return void
	 */
	public function compileDevelopment(Collection $collection, $group)
	{
		$responses = parent::compile($collection, $group);

		// The output path will be the supplied output path plus the name of the collection.
		// This helps to organise assets for each collection during development.
		$outputPath = "{$this->outputPath}/{$collection->getName()}";

		if ( ! $this->files->exists($outputPath))
		{
			$this->files->makeDirectory($outputPath);
		}

		$extension = $this->determineExtension($group);

		// Spin through each of the responses and create the relevant directories and files for
		// each of them.
		foreach ($responses as $file => $contents)
		{
			$pathInfo = pathinfo($file);

			$outputFilePath = "{$outputPath}/{$pathInfo['dirname']}";

			if ( ! $this->files->exists($outputFilePath))
			{
				$this->files->makeDirectory($outputFilePath);
			}

			$this->files->put("{$outputFilePath}/{$pathInfo['filename']}.{$extension}", $contents);
		}
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
	 * Alias for JasonLewis\Basset\Compiler\FilesystemCompiler::setForce(true)
	 * 
	 * @return JasonLewis\Basset\Compiler\FilesystemCompiler
	 */
	public function force()
	{
		return $this->setForce(true);
	}

	/**
	 * Set the compiling to be forced.
	 * 
	 * @param  bool  $force
	 * @return JasonLewis\Basset\Compiler\FilesystemCompiler
	 */
	public function setForce($force)
	{
		$this->force = $force;

		return $this;
	}

}