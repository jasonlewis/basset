<?php namespace Basset\Console;

use Basset\BuildCleaner;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

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
    protected $description = 'Clean built collections';

    /**
     * Build cleaner instance.
     *
     * @var Basset\BuildCleaner
     */
    protected $cleaner;

    /**
     * Create a new clean command instance.
     *
     * @param  Basset\BuildCleaner  $cleaner
     * @return void
     */
    public function __construct(BuildCleaner $cleaner)
    {
        parent::__construct();

        $this->cleaner = $cleaner;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if ($collection = $this->input->getArgument('collection'))
        {
            $this->line("<info>Cleaning up collection:</info> {$collection}");
        }
        else
        {
            $this->info('Cleaning up all collections.');
        }

        $this->cleaner->clean($collection);

        $this->info('Done!');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('collection', InputArgument::OPTIONAL, 'The asset collection to clean'),
        );
    }

}