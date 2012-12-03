<?php namespace Basset\Console;

use Illuminate\Console\Command;

class ListCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'basset:list';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'List all collections and compile statuses';

	/**
	 * Illuminate application instance.
	 * 
	 * @var Illuminate\Foundation\Application
	 */
	protected $app;

	/**
	 * Path where assets are compiled.
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
	public function __construct($app, $compilePath)
	{
		parent::__construct();

		$this->app = $app;
		$this->compilePath = $compilePath;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$collections = $this->app['basset']->getCollections();

		if ( ! empty($collections))
		{
			$this->line('');
		}

		foreach ($collections as $collection)
		{
			$this->comment($collection->getName().':');

			$assets = $collection->getAssets();

			$this->line('   Styles:  '.(isset($assets['style']) ? ($collection->isCompiled('style') ? '<info>Compiled</info>' : '<comment>Uncompiled or needs re-compiling</comment>') : 'None available'));

			$this->line('   Scripts: '.(isset($assets['script']) ? ($collection->isCompiled('script') ? '<info>Compiled</info>' : '<comment>Uncompiled or needs re-compiling</comment>') : 'None available'));
		}

		$this->line('');
		$this->line('Total collections: '.count($collections));
	}

}