<?php namespace Basset\Console;

use Illuminate\Console\Command;

class CleanCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'basset:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete compiled collections that are no longer needed.';

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
        $this->info("\nAttempting to clean up compiled collections...");

        // Get all files in the compile paths
        $this->info('Gathering all compiled collections...');
        $compiledFiles = $this->app['files']->files($this->compilePath);

        if (empty($compiledFiles))
        {
            // Nothing to clean
            $this->line("\nNo files to clean");
        }

        foreach ($compiledFiles as $index => $path)
        {
            $compiledFiles[$index] = pathinfo($path, PATHINFO_BASENAME);
        }

        // Get the active files
        $this->info('Gathering active collections...');

        $activeFiles = array();
        $collections = $this->app['basset']->getCollections();

        foreach ($collections as $collection)
        {
            $assets = $collection->getAssets();

            foreach (array('style', 'script') as $type)
            {
                if (isset($assets[$type]))
                {
                    $activeFiles[] = $collection->getCompiledName($type);
                }
            }
        }

        // Remove old compiled files
        $this->line("\nCleaning up files:");

        $removeFiles = array_diff($compiledFiles, $activeFiles);

        if (empty($removeFiles))
        {
            $this->line('   No files to clean');
        }
        else
        {
            $this->line('');

            foreach ($removeFiles as $file)
            {
                $this->line('   Cleaned: <comment>'.$file.'</comment>');
                $this->app['files']->delete($this->compilePath.'/'.$file);
            }
        }

        $this->line("\nTotal files cleaned: ".count($removeFiles));
    }
}
