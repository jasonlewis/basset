<?php namespace Basset\Console;

use Basset\Basset;
use RuntimeException;
use Illuminate\Console\Command;
use Basset\Manifest\Repository;
use Symfony\Component\Console\Input\InputOption;
use Basset\Compiler\CompilerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Basset\Exception\EmptyResponseException;
use Basset\Exception\CollectionExistsException;

class CompileCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'basset:compile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compile asset collections';

    /**
     * Basset instance.
     *
     * @var Basset\Basset
     */
    protected $basset;

    /**
     * Filesystem compiler instance.
     *
     * @var Basset\Compiler\FilesystemCompiler
     */
    protected $compiler;

    /**
     * Manifest repository instance.
     *
     * @var Basset\Manifest\Repository
     */
    protected $repository;

    /**
     * Path to output compiled collections.
     *
     * @var string
     */
    protected $compilePath;

    /**
     * Create a new basset compile command instance.
     *
     * @param  Basset\Basset  $basset
     * @param  Basset\Compiler\CompilerInterface  $compiler
     * @return void
     */
    public function __construct(Basset $basset, CompilerInterface $compiler, Repository $repository, $compilePath)
    {
        parent::__construct();

        $this->basset = $basset;
        $this->compiler = $compiler;
        $this->repository = $repository;
        $this->compilePath = $compilePath;
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
            $this->info("Gathering all collections to compile...");

            $collections = $this->basset->getCollections();
        }

        // If the force option has been set then we'll tell the compiler that the collections
        // are to be forcefully compiled.
        $this->input->getOption('force') and $this->compiler->force();

        $this->compiler->setCompilePath($this->compilePath);

        // Spin through each of the collections and compile both the scripts and styles. Each is broken into
        // a separate try/catch block so that we can catch any exceptions thrown when attempting to compile.
        // We'll also handle the compiling of development assets here as well.
        foreach ($collections as $collection)
        {
            try
            {
                if ($this->input->getOption('dev'))
                {
                    $this->compiler->compileDevelopment($collection, 'styles');
                }
                else
                {
                    $this->compiler->compileStyles($collection);
                }

                $this->line("<info>Styles successfully compiled for collection:</info> {$collection->getName()}");
            }
            catch (EmptyResponseException $error)
            {
                $this->line("<comment>There are no styles to compile for collection:</comment> {$collection->getName()}");
            }
            catch (CollectionExistsException $error)
            {
                $this->line("<comment>Styles are up-to-date for collection:</comment> {$collection->getName()}");
            }

            try
            {
                if ($this->input->getOption('dev'))
                {
                    $this->compiler->compileDevelopment($collection, 'scripts');
                }
                else
                {
                    $this->compiler->compileScripts($collection);
                }

                $this->line("<info>Scripts successfully compiled for collection:</info> {$collection->getName()}");
            }
            catch (EmptyResponseException $error)
            {
                $this->line("<comment>There are no scripts to compile for collection:</comment> {$collection->getName()}");
            }
            catch (CollectionExistsException $error)
            {
                $this->line("<comment>Scripts are up-to-date for collection:</comment> {$collection->getName()}");
            }

            // After the compiling has taken place we'll register this collection with the repository. This will
            // store all information related to this collection so that Basset knows what files to load when
            // running under different environments.
            $this->repository->register($collection, $this->compiler->getFingerprint(), $this->input->getOption('dev'));
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
            array('collection', InputArgument::OPTIONAL, 'The asset collection to compile'),
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
            array('force', 'f', InputOption::VALUE_NONE, 'Forces a re-compile of the collection'),
            array('dev', null, InputOption::VALUE_NONE, 'Compile assets individually for development'),
        );
    }

}