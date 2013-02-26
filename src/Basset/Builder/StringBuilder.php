<?php namespace Basset\Builder;

use Basset\Collection;
use Basset\Exception\EmptyResponseException;

class StringBuilder extends Builder {

    /**
     * Build the assets of a collection.
     *
     * @param  Basset\Collection  $collection
     * @return array
     */
    public function build(Collection $collection, $group)
    {
        $collection->processCollection();

        $responses = array();

        foreach ($collection->getAssets($group) as $asset)
        {
            // If the asset is ignored or is a remote asset and the building of remotes is disabled then we'll
            // skip to the next asset.
            if ($asset->isIgnored() or ($asset->isRemote() and ! $this->config->get('basset::build_remotes', false)))
            {
                continue;
            }

            $responses[$asset->getRelativePath()] = $asset->build();
        }

        if ( ! $responses)
        {
            throw new EmptyResponseException("No [{$group}] assets built for collection [{$collection->getName()}]");
        }

        return $responses;
    }

}