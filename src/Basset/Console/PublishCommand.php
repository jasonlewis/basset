<?php namespace Basset\Console;

use Basset\Environment;
use Basset\AssetPublisher;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class PublishCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'basset:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish a collections assets to public';

    /**
     * Basset environment instance.
     * 
     * @var \Basset\Environment
     */
    protected $environment;

    /**
     * Basset asset publisher instance.
     * 
     * @var \Basset\AssetPublisher
     */
    protected $publisher;

    /**
     * Create a new basset command instance.
     * 
     * @param  \Basset\Environment  $environment
     * @param  \Basset\AssetPublisher  $publisher
     * @return void
     */
    public function __construct(Environment $environment, AssetPublisher $publisher)
    {
        parent::__construct();

        $this->environment = $environment;
        $this->publisher = $publisher;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $collections = $this->gatherCollections();

        foreach ($collections as $identifier => $collection)
        {
            $publishQueue = $collection->getPublishQueue();

            if (empty($publishQueue))
            {
                $this->line('<comment>['.$identifier.']</comment> No publish queue for collection.');
            }
            else
            {
                $published = $this->publisher->publish($publishQueue);

                if (empty($published))
                {
                    $this->line('<comment>['.$identifier.']</comment> No outstanding assets to publish.');
                }
                else
                {
                    foreach ($published as $original => $published)
                    {
                        $this->line('<info>['.$identifier.']</info> Published '.$original.' to '.$published);
                    }
                }
            }
        }

        $this->line('');
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
            if ( ! $this->environment->has($collection))
            {
                $this->comment('['.$collection.'] Collection not found.');

                return array();
            }

            $this->comment('Checking collection for publish queue...');

            $collections = array($collection => $this->environment->collection($collection));
        }
        else
        {
            $this->comment('Checking publish queue of all collections...');

            $collections = $this->environment->all();
        }

        $this->line('');

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
            array('collection', InputArgument::OPTIONAL, 'Collection whose assets will be published'),
        );
    }

}