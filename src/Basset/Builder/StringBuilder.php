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
        $responses = array();

        foreach ($collection->getAssets($group) as $asset)
        {
            if ($asset->isExcluded())
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