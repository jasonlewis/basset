<?php namespace Basset\Factory;

interface FactoryInterface {

    /**
     * Make a new factory instance.
     * 
     * @param  string  $factory
     * @return mixed
     */
    public function make($factory);

}