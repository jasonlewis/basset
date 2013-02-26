<?php namespace Basset\Builder;

use Basset\Collection;

interface BuilderInterface {

    /**
     * Build the assets of a collection.
     *
     * @param  Basset\Collection  $collection
     * @param  string  $group
     * @return mixed
     */
    public function build(Collection $collection, $group);

}