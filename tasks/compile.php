<?php

use Basset\Collection;

class Basset_Compile_Task {

	/**
	 * The path to compile assets to.
	 * 
	 * @var string
	 */
	protected $compilePath;

	/**
	 * Create a new basset compile command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->compilePath = path('public').Config::get('basset::basset.compiling_path');
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function run($arguments)
	{
		if ( ! empty($arguments))
		{
			$collection = $arguments[0];

			if ( ! Basset::hasCollection($collection))
			{
				echo "Oops! Could not find collection: {$collection}";

				return;
			}

			echo "Gathering assets for collection...\n";

			$collections = array(Basset::collection($collection));
		}
		else
		{
			echo "Gathering collections to compile...\n";

			$collections = Basset::getCollections();
		}

		// Spin through and compile each of the collections.
		foreach ($collections as $collection)
		{
			$this->compile($collection);
		}

		echo "\nDone!";
	}

	/**
	 * Compile an asset collection.
	 * 
	 * @param  Basset\Collection  $collection
	 * @return void
	 */
	protected function compile(Collection $collection)
	{
		$force = isset($_SERVER['CLI']['FORCE']);

		// If the compile path does not exist attempt to create it.
		if ( ! File::exists($compilePath))
		{
			File::mkdir($compilePath);
		}

		$groups = $collection->getAssets();

		if (empty($groups))
		{
			echo "The collection '{$collection->getName()}' has no assets to compile.\n";
		}

		foreach ($groups as $group => $assets)
		{
			$path = $compilePath.'/'.$collection->getCompiledName($group);

			// We only compile a collection if a compiled file doesn't exist yet or if a change to one of the assets
			// in the collection is detected by comparing the last modified times.
			if (File::exists($path) and File::modified($path) >= $collection->lastModified($group))
			{
				// If the force flag has been set then we'll recompile, otherwise this collection does not need
				// to be changed.
				if ( ! $force)
				{
					echo "The {$group}s for the collection '{$collection->getName()}' do not need to be compiled.\n";
				
					continue;
				}
			}

			$compiled = $collection->compile($group);

			echo "Successfully compiled {$collection->getCompiledName($group)}\n";

			File::put($path, $compiled);
		}
	}

}