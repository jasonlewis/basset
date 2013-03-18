<?php namespace Basset\Console;

use RuntimeException;
use Basset\Collection;
use Basset\Environment;
use Basset\BuildCleaner;
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
     * Environment instance.
     *
     * @var Basset\Environment
     */
    protected $env;

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
    protected $manifest;

    /**
     * Build cleaner instance.
     *
     * @var Basset\BuildCleaner
     */
    protected $cleaner;

    /**
     * Path to output built collections.
     *
     * @var string
     */
    protected $buildPath;

    /**
     * Create a new basset compile command instance.
     *
     * @param  Basset\Environment  $env
     * @param  Basset\Builder\BuilderInterface  $builder
     * @return void
     */
    public function __construct(Environment $env, BuilderInterface $builder, Repository $manifest, BuildCleaner $cleaner, $buildPath)
    {
        parent::__construct();

        $this->env = $env;
        $this->builder = $builder;
        $this->manifest = $manifest;
        $this->cleaner = $cleaner;
        $this->buildPath = $buildPath;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $collections = $this->fetchCollections();

        // If the force option has been set then we'll tell the builder to forcefully re-build each
        // of the collections.
        $this->input->getOption('force') and $this->builder->force();

        $this->builder->setBuildPath($this->buildPath);

        // Spin through each of the collections to be built and build both the scripts and styles. We'll catch
        // any exceptions thrown during the building of the assets and output friendlier messages to the user.
        foreach ($collections as $collection)
        {
            try
            {
                $this->build($collection, 'stylesheets');

                $this->line("<info>Stylesheets successfully built for collection:</info> {$collection->getName()}");
            }
            catch (EmptyResponseException $error)
            {
                $this->line("<comment>There are no stylesheets to build for collection:</comment> {$collection->getName()}");
            }
            catch (CollectionExistsException $error)
            {
                $this->line("<comment>Stylesheets are up-to-date for collection:</comment> {$collection->getName()}");
            }

            try
            {
                $this->build($collection, 'javascripts');

                $this->line("<info>Javascripts successfully built for collection:</info> {$collection->getName()}");
            }
            catch (EmptyResponseException $error)
            {
                $this->line("<comment>There are no javascripts to build for collection:</comment> {$collection->getName()}");
            }
            catch (CollectionExistsException $error)
            {
                $this->line("<comment>Javascripts are up-to-date for collection:</comment> {$collection->getName()}");
            }

            // Once a collection has been built we need to register the collection with the manifest repository. The
            // repository will store details about the collection in the manifest.
            $this->manifest->register($collection, $this->builder->getFingerprint(), $this->input->getOption('dev'));
        }

        // After building all the collections we'll let the cleaner tidy up any unnecessary asset files.
        $this->cleaner->clean();
    }

    /**
     * Build a given group on the collection.
     *
     * @param  Basset\Collection  $collection
     * @param  string  $group
     * @return void
     */
    protected function build(Collection $collection, $group)
    {
        $this->builder->build($collection, $group, $this->input->getOption('dev'));
    }

    /**
     * Fetch the collections to be built.
     *
     * @return array
     */
    protected function fetchCollections()
    {
        if ( ! is_null($collection = $this->input->getArgument('collection')))
        {
            if ( ! $this->env->hasCollection($collection))
            {
                $this->error("Could not find collection: {$collection}");

                return;
            }

            $this->info("Gathering assets for collection...");

            $collections = array($this->env->collection($collection));
        }
        else
        {
            $this->info("Gathering all collections to build...");

            $collections = $this->env->getCollections();
        }

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
            array('force', 'f', InputOption::VALUE_NONE, 'Forces a re-build of the collection')
        );
    }

}