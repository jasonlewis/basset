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

        // We'll store each of the assets built response in an array so that we can join each
        // response by a new line with implode.
        $responses = array();

        foreach ($collection->getAssets($group) as $asset)
        {
            // If remote assets are not to be built and the asset is remote or the asset is being
            // ignored then it won't be included.
            if ($asset->isIgnored() or ($asset->isRemote() and ! $this->config->get('basset::build_remotes', false)))
            {
                continue;
            }

            $responses[$asset->getRelativePath()] = $asset->build();
        }

        // If no assets were built then we'll throw an exception.
        if (empty($responses))
        {
            throw new EmptyResponseException("No [{$group}] assets built for collection [{$collection->getName()}]");
        }

        return $responses;
    }

}