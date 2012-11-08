<?php namespace Basset\Console;

use Basset\Basset;
use Basset\Collection;
use Illuminate\Filesystem;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CompileCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'basset:compile';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Compile asset collections';

	/**
	 * Basset instance.
	 * 
	 * @var Basset\Basset
	 */
	protected $basset;

	/**
	 * Illuminate filesystem instance.
	 * 
	 * @var Illuminate\Filesystem
	 */
	protected $files;

	/**
	 * Path where assets are published.
	 * 
	 * @var string
	 */
	protected $compilePath;

	/**
	 * Create a new basset compile command instance.
	 *
	 * @param  Basset\Basset  $basset
	 * @param  Illuminate\Filesystem  $files
	 * @return void
	 */
	public function __construct(Basset $basset, Filesystem $files, $compilePath)
	{
		parent::__construct();

		$this->basset = $basset;
		$this->files = $files;
		$this->compilePath = $compilePath;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->line('');

		if ( ! is_null($collection = $this->input->getArgument('collection')))
		{
			if ( ! $this->basset->hasCollection($collection))
			{
				$this->error("Oops! Could not find collection: {$collection}");

				return $this->line("");
			}

			$this->info("Gathering assets for collection...");

			$collections = array($this->basset->collection($collection));
		}
		else
		{
			$this->info("Gathering collections to compile...");

			$collections = $this->basset->getCollections();
		}

		// Spin through and compile each of the collections.
		foreach ($collections as $collection)
		{
			$this->compile($collection);
		}

		$this->output->writeln("\nDone!\n");
	}

	/**
	 * Compile an asset collection.
	 * 
	 * @param  Basset\Collection  $collection
	 * @return void
	 */
	protected function compile(Collection $collection)
	{
		// If the compile path does not exist attempt to create it.
		if ( ! $this->files->exists($this->compilePath))
		{
			$this->files->makeDirectory($this->compilePath);
		}

		$groups = $collection->getAssets();

		if (empty($groups))
		{
			$this->comment("The collection '{$collection->getName()}' has no assets to compile.");
		}

		foreach ($groups as $group => $assets)
		{
			$path = $this->compilePath.'/'.$collection->getCompiledName($group);

			// We only compile a collection if a compiled file doesn't exist yet or if a change to one of the assets
			// in the collection is detected by comparing the last modified times.
			if ($this->files->exists($path) and $this->files->lastModified($path) >= $collection->lastModified($group))
			{
				// If the force flag has been set then we'll recompile, otherwise this collection does not need
				// to be changed.
				if ( ! $this->input->getOption('force'))
				{
					$this->comment("The {$group}s for the collection '{$collection->getName()}' do not need to be compiled.");
				
					continue;
				}
			}

			$compiled = $collection->compile($group);

			$this->info("Successfully compiled {$collection->getCompiledName($group)}");

			$this->files->put($path, $compiled);
		}
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('collection', InputArgument::OPTIONAL, 'The asset collection to compile'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('force', 'f', InputOption::VALUE_NONE, 'Force a re-compile of collection')
		);
	}

}