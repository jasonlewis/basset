<?php namespace Basset\Console;

use Basset\Basset;
use RuntimeException;
use Basset\Collection;
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
     * @param  Basset\Basset  $basset
     * @param  Basset\Builder\BuilderInterface  $builder
     * @return void
     */
    public function __construct(Basset $basset, BuilderInterface $builder, Repository $manifest, BuildCleaner $cleaner, $buildPath)
    {
        parent::__construct();

        $this->basset = $basset;
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
                $this->build($collection, 'styles');

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
                $this->build($collection, 'scripts');

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
        if ($this->input->getOption('dev'))
        {
            $this->builder->buildDevelopment($collection, $group);
        }
        else
        {
            $this->builder->{'build'.camel_case($group)}($collection);
        }
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
            array('force', 'f', InputOption::VALUE_NONE, 'Forces a re-build of the collection'),
            array('dev', null, InputOption::VALUE_NONE, 'Build assets individually for development'),
        );
    }

}