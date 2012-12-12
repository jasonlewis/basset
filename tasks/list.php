<?php

class Basset_List_Task {

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
	public function run()
	{
		$collections = Basset::getCollections();

		foreach ($collections as $collection)
		{
			echo $collection->getName().":\n";

			$assets = $collection->getAssets();

			echo '   Styles:  '.(isset($assets['style']) ? ($collection->isCompiled('style') ? 'Compiled' : 'Uncompiled or needs re-compiling') : 'None available');

			echo "\n";

			echo '   Scripts: '.(isset($assets['script']) ? ($collection->isCompiled('script') ? 'Compiled' : 'Uncompiled or needs re-compiling') : 'None available');

			echo "\n";
		}

		echo "\nTotal collections: ".count($collections);
	}

}