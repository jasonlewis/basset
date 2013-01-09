<?php namespace Basset;

use Illuminate\Filesystem;

class CollectionCompiler {

	/**
	 * Filesystem instance.
	 * 
	 * @var Illuminate\Filesystem
	 */
	protected $files;

	/**
	 * Basset instance.
	 * 
	 * @var Basset\Basset
	 */
	protected $basset;

	/**
	 * Path to store compiled assets.
	 * 
	 * @var string
	 */
	protected $compilePath;

	/**
	 * Create a new collection compiler instance.
	 * 
	 * @param  Illuminate\Filesystem  $files
	 * @param  Basset\Basset  $basset
	 * @param  string  $compilePath
	 * @return void
	 */
	public function __construct(Filesystem $files, Basset $basset, $compilePath)
	{
		$this->files = $files;
		$this->basset = $basset;
		$this->compilePath = $compilePath;
	}

	/**
	 * Compile an asset collection.
	 * 
	 * @param  Basset\Collection  $collection
	 * @param  bool  $force
	 * @return void
	 */
	public function compile(Collection $collection, $force = false)
	{
		// If the compile path does not exist attempt to create it.
		if ( ! $this->files->exists($this->compilePath))
		{
			$this->files->makeDirectory($this->compilePath);
		}

		$groups = $collection->getAssets();

		if (empty($groups))
		{
			return false;
		}

		$response = array();

		foreach ($groups as $group => $assets)
		{
			$path = $this->compilePath.'/'.$collection->getCompiledName($group);

			// We only compile a collection if a compiled file doesn't exist yet or if a change to one of the assets
			// in the collection is detected by comparing the last modified times.
			if ($this->files->exists($path) and $this->files->lastModified($path) >= $collection->lastModified($group))
			{
				// If the force flag has been set then we'll recompile, otherwise this collection does not need
				// to be changed.
				if ( ! $force)
				{
					$response[] = "<comment>The {$group}s for the collection '{$collection->getName()}' do not need to be compiled.</comment>";
				
					continue;
				}
			}

			$compiled = $collection->compile($group);

			$response[] = "<info>Successfully compiled {$collection->getCompiledName($group)}</info>";

			$this->files->put($path, $compiled);
		}

		return $response;
	}

}