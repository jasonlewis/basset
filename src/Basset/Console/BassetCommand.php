<?php namespace Basset\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Basset\Builder\FilesystemCleaner;
use Basset\BassetServiceProvider as Basset;
use Symfony\Component\Console\Input\InputOption;

class BassetCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'basset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interact with the Basset package';

    /**
     * Illuminate filesystem instance.
     * 
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Basset filesystem cleaner instance.
     * 
     * @var \Basset\Builder\FilesystemCleaner
     */
    protected $cleaner;

    /**
     * Path to the manifest storage.
     * 
     * @var string
     */
    protected $manifestPath;

    /**
     * Create a new basset command instance.
     * 
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Basset\Builder\FilesystemCleaner  $cleaner
     * @param  string  $manifestPath
     * @return void
     */
    public function __construct(Filesystem $files, FilesystemCleaner $cleaner, $manifestPath)
    {
        parent::__construct();

        $this->files = $files;
        $this->cleaner = $cleaner;
        $this->manifestPath = $manifestPath;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if ( ! $this->input->getOption('reset-manifest') and ! $this->input->getOption('tidy-up'))
        {
            $this->line('<info>Basset</info> version <comment>'.Basset::VERSION.'</comment>');
        }
        else
        {
            if ($this->input->getOption('reset-manifest'))
            {
                $this->resetCollectionManifest();
            }

            if ($this->input->getOption('tidy-up'))
            {
                $this->tidyUpFilesystem();
            }
        }
    }

    /**
     * Reset the collection manifest by deleting the file.
     * 
     * @return void
     */
    protected function resetCollectionManifest()
    {
        if ($this->files->exists($path = $this->manifestPath.'/collections.json'))
        {
            $this->files->delete($path);

            $this->info('Collection manifest has been successfully reset. All collections will need to be rebuilt.');
        }
        else
        {
            $this->comment('Collection manifest does not need to be reset.');
        }
    }

    /**
     * Tidy up the filesystem with the build cleaner.
     * 
     * @return void
     */
    protected function tidyUpFilesystem()
    {
        $collections = $this->input->getOption('tidy-up');

        if ( ! array_filter($collections))
        {
            $this->cleaner->clean();

            $this->info('Outdated collections on the filesystem have been tidied up.');
        }
        else
        {
            foreach (array_unique($collections) as $collection)
            {
                $this->cleaner->clean($collection);

                $this->line('<info>Outdated collection on the filesystem has been tidied up:</info> '.$collection);
            }
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('reset-manifest', null, InputOption::VALUE_NONE, 'Reset the collection manifest'),
            array('tidy-up', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Tidy up the outdated collections on the filesystem')
        );
    }

}