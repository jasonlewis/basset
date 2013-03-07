<?php namespace Basset\Console;

use Illuminate\Console\Command;
use Basset\BassetServiceProvider as Basset;

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
    protected $description = 'Get Basset version information';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->line('<info>Basset</info> version <comment>'.Basset::VERSION.'</comment>');
    }

}