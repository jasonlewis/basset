<?php namespace JasonLewis\Basset\Console;

use RuntimeException;
use JasonLewis\Basset\Factory;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use JasonLewis\Basset\Compiler\CompilerInterface;
use Symfony\Component\Console\Input\InputArgument;
use JasonLewis\Basset\Exceptions\NoAssetsCompiledException;
use JasonLewis\Basset\Exceptions\CompilingNotRequiredException;

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
	 * Basset factory instance.
	 * 
	 * @var JasonLewis\Basset\Factory
	 */
	protected $factory;

	/**
	 * Filesystem compiler instance.
	 * 
	 * @var JasonLewis\Basset\Compiler\FilesystemCompiler
	 */
	protected $compiler;

	/**
	 * Path to output compiled collections.
	 * 
	 * @var string
	 */
	protected $outputPath;

	/**
	 * Create a new basset compile command instance.
	 *
	 * @param  JasonLewis\Basset\Factory  $factory
	 * @param  JasonLewis\Basset\Compiler\CompilerInterface  $compiler
	 * @return void
	 */
	public function __construct(Factory $factory, CompilerInterface $compiler, $outputPath)
	{
		parent::__construct();

		$this->factory = $factory;
		$this->compiler = $compiler;
		$this->outputPath = $outputPath;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		if ( ! is_null($collection = $this->input->getArgument('collection')))
		{
			if ( ! $this->factory->hasCollection($collection))
			{
				$this->error("Could not find collection: {$collection}");

				return;
			}

			$this->info("Gathering assets for collection...");

			$collections = array($this->factory->collection($collection));
		}
		else
		{
			$this->info("Gathering all collections to compile...");

			$collections = $this->factory->getCollections();
		}

		// If the force option has been set then we'll tell the compiler that the collections
		// are to be forcefully compiled.
		if ($this->input->getOption('force'))
		{
			$this->compiler->setForce(true);
		}

		$this->compiler->setOutputPath($this->outputPath);

		// Spin through each of the collections and compile both the scripts and styles. Each is broken into
		// a separate try/catch block so that we can catch any exceptions thrown when attempting to compile.
		// We'll also handle the compiling of development assets here as well.
		foreach ($collections as $collection)
		{
			try
			{
				if ($this->input->getOption('dev'))
				{
					$this->compiler->compileDevelopment($collection, 'styles');
				}
				else
				{
					$this->compiler->compileStyles($collection);
				}

				$this->line("<info>Styles successfully compiled for collection:</info> {$collection->getName()}");
			}
			catch (NoAssetsCompiledException $error)
			{
				$this->line("<comment>There are no styles to compile for collection:</comment> {$collection->getName()}");
			}
			catch (CompilingNotRequiredException $error)
			{
				$this->line("<comment>Styles are up-to-date for collection:</comment> {$collection->getName()}");
			}

			try
			{
				if ($this->input->getOption('dev'))
				{
					$this->compiler->compileDevelopment($collection, 'scripts');
				}
				else
				{
					$this->compiler->compileScripts($collection);
				}

				$this->line("<info>Scripts successfully compiled for collection:</info> {$collection->getName()}");
			}
			catch (NoAssetsCompiledException $error)
			{
				$this->line("<comment>There are no scripts to compile for collection:</comment> {$collection->getName()}");
			}
			catch (CompilingNotRequiredException $error)
			{
				$this->line("<comment>Scripts are up-to-date for collection:</comment> {$collection->getName()}");
			}
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
			array('force', 'f', InputOption::VALUE_NONE, 'Forces a re-compile of the collection'),
			array('dev', null, InputOption::VALUE_NONE, 'Compile assets individually for development'),
		);
	}

}