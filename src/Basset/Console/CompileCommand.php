<?php namespace Basset\Console;

use Basset\Basset;
use Basset\CollectionCompiler;
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
	 * Collection compiler instance.
	 * 
	 * @var Basset\CollectionCompiler
	 */
	protected $compiler;

	/**
	 * Create a new basset compile command instance.
	 *
	 * @param  Basset\Basset  $basset
	 * @param  Basset\CollectionCompiler  $compiler
	 * @return void
	 */
	public function __construct(Basset $basset, CollectionCompiler $compiler)
	{
		parent::__construct();

		$this->basset = $basset;
		$this->compiler = $compiler;
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
			if ($response = $this->compiler->compile($collection))
			{
				$this->line($response);
			}
			else
			{
				$this->comment("The collection '{$collection->getName()}' has no assets to compile.");
			}
		}

		$this->line("\nDone!\n");
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
			array('force', 'f', InputOption::VALUE_NONE, 'Forces a re-compile of the collection')
		);
	}

}