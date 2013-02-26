<?php namespace Basset\Console;

use Basset\Basset;
use RuntimeException;
use Illuminate\Console\Command;
use Basset\Manifest\Repository;
use Basset\Builder\BuilderInterface;
use Basset\Exception\EmptyResponseException;
use Basset\Exception\CollectionExistsException;
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
     * Basset instance.
     *
     * @var Basset\Basset
     */
    protected $basset;

    /**
     * Builder instance.
     *
     * @var Basset\Builder\BuilderInterface
     */
    protected $builder;

    /**
     * Manifest repository instance.
     *
     * @var Basset\Manifest\Repository
     */
    protected $repository;

    /**
     * Path to output built collections.
     *
     * @var string
     */
    protected $buildPath;

    /**
     * Create a new basset compile command instance.
     *
     * @param  Basset\Basset  $basset
     * @param  Basset\Builder\BuilderInterface  $builder
     * @return void
     */
    public function __construct(Basset $basset, BuilderInterface $builder, Repository $repository, $buildPath)
    {
        parent::__construct();

        $this->basset = $basset;
        $this->builder = $builder;
        $this->repository = $repository;
        $this->buildPath = $buildPath;
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
            if ( ! $this->basset->hasCollection($collection))
            {
                $this->error("Could not find collection: {$collection}");

                return;
            }

            $this->info("Gathering assets for collection...");

            $collections = array($this->basset->collection($collection));
        }
        else
        {
            $this->info("Gathering all collections to build...");

            $collections = $this->basset->getCollections();
        }

        // If the force option has been set then we'll tell the builder that the collections
        // are to be forcefully built.
        $this->input->getOption('force') and $this->builder->force();

        $this->builder->setBuildPath($this->buildPath);

        // Spin through each of the collections and build both the scripts and styles. Each is broken into
        // a separate try/catch block so that we can catch any exceptions thrown when attempting to build.
        // We'll also handle the building of development assets here as well.
        foreach ($collections as $collection)
        {
            try
            {
                if ($this->input->getOption('dev'))
                {
                    $this->builder->buildDevelopment($collection, 'styles');
                }
                else
                {
                    $this->builder->buildStyles($collection);
                }

                $this->line("<info>Styles successfully built for collection:</info> {$collection->getName()}");
            }
            catch (EmptyResponseException $error)
            {
                $this->line("<comment>There are no styles to build for collection:</comment> {$collection->getName()}");
            }
            catch (CollectionExistsException $error)
            {
                $this->line("<comment>Styles are up-to-date for collection:</comment> {$collection->getName()}");
            }

            try
            {
                if ($this->input->getOption('dev'))
                {
                    $this->builder->buildDevelopment($collection, 'scripts');
                }
                else
                {
                    $this->builder->buildScripts($collection);
                }

                $this->line("<info>Scripts successfully built for collection:</info> {$collection->getName()}");
            }
            catch (EmptyResponseException $error)
            {
                $this->line("<comment>There are no scripts to build for collection:</comment> {$collection->getName()}");
            }
            catch (CollectionExistsException $error)
            {
                $this->line("<comment>Scripts are up-to-date for collection:</comment> {$collection->getName()}");
            }

            // After the building has taken place we'll register this collection with the repository. This will
            // store all information related to this collection so that Basset knows what files to load when
            // running under different environments.
            $this->repository->register($collection, $this->builder->getFingerprint(), $this->input->getOption('dev'));
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
            array('force', 'f', InputOption::VALUE_NONE, 'Forces a re-build of the collection'),
            array('dev', null, InputOption::VALUE_NONE, 'Build assets individually for development'),
        );
    }

}