<?php namespace Basset\Compiler;

use Basset\Collection;

interface CompilerInterface {

    /**
     * Compile the assets of a collection.
     *
     * @param  Basset\Collection  $collection
     * @param  string  $group
     * @return mixed
     */
    public function compile(Collection $collection, $group);

}