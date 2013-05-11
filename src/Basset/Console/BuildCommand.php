<?php namespace Basset\Console;

use RuntimeException;
use Basset\Collection;
use Basset\Environment;
use Basset\Builder\Builder;
use Illuminate\Console\Command;
use Basset\Manifest\Repository;
use Basset\Exceptions\BuildNotRequiredException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class BuildCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'basset:build';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build asset collections';

    /**
     * Basset environment instance.
     *
     * @var \Basset\Environment
     */
    protected $environment;

    /**
     * Basset builder instance.
     *
     * @var \Basset\Builder\Builder
     */
    protected $builder;

    /**
     * Create a new basset compile command instance.
     *
     * @param  \Basset\Environment  $environment
     * @param  \Basset\Builder\Builder  $builder
     * @return void
     */
    public function __construct(Environment $environment, Builder $builder)
    {
        parent::__construct();

        $this->environment = $environment;
        $this->builder = $builder;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->input->getOption('force') and $this->builder->setForce(true);

        $this->input->getOption('gzip') and $this->builder->setGzip(true);

        if ($development = $this->input->getOption('dev'))
        {
            $this->comment("Starting development build....");
        }
        else
        {
            $this->comment("Starting production build....");
        }

        foreach ($this->gatherCollections() as $collection)
        {
            if ($development)
            {
                $this->buildAsDevelopment($collection);
            }
            else
            {
                $this->buildAsProduction($collection);
            }
        }
    }

    /**
     * Dynamically handle calls to the build methods.
     * 
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, array('buildAsDevelopment', 'buildAsProduction')))
        {
            $collection = array_shift($parameters);

            try
            {
                $this->builder->{$method}($collection, 'stylesheets');

                $this->line("<info>Stylesheets successfully built for collection:</info> {$collection->getName()}");
            }
            catch (BuildNotRequiredException $error)
            {
                $this->line("<comment>Stylesheets build was not required for collection:</comment> {$collection->getName()}");
            }

            try
            {
                $this->builder->{$method}($collection, 'javascripts');

                $this->line("<info>Javascripts successfully built for collection:</info> {$collection->getName()}");
            }
            catch (BuildNotRequiredException $error)
            {
                $this->line("<comment>Javascripts build was not required for collection:</comment> {$collection->getName()}");
            }
        }
        else
        {
            return parent::__call($method, $parameters);
        }
    }

    /**
     * Gather the collections to be built.
     *
     * @return array
     */
    protected function gatherCollections()
    {
        if ( ! is_null($collection = $this->input->getArgument('collection')))
        {
            if ( ! $this->environment->hasCollection($collection))
            {
                $this->error("Could not find collection: {$collection}");

                return;
            }

            $this->comment("Gathering assets for collection...");

            $collections = array($this->environment->collection($collection));
        }
        else
        {
            $this->comment("Gathering all collections to build...");

            $collections = $this->environment->getCollections();
        }

        $this->line("");

        return $collections;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('collection', InputArgument::OPTIONAL, 'The asset collection to build'),
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
            array('dev', null, InputOption::VALUE_NONE, 'Build assets for a development environment'),
            array('gzip', null, InputOption::VALUE_NONE, 'Gzip built assets'),
            array('force', 'f', InputOption::VALUE_NONE, 'Forces a re-build of the collection')
        );
    }

}