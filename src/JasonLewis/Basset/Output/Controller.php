<?php namespace JasonLewis\Basset\Output;

use Illuminate\Routing\Controllers\Controller as IlluminateController;

class Controller extends IlluminateController {

    /**
     * Create a new output controller instance.
     *
     * @param  JasonLewis\Basset\Output\Builder  $builder
     * @return void
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Process an asset with the builder.
     *
     * @param  string  $path
     * @return string
     */
    public function processAsset($path)
    {
        dd($this->builder);
    }

}