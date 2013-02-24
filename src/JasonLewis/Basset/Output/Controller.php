<?php namespace JasonLewis\Basset\Output;

use Illuminate\Routing\Controllers\Controller as IlluminateController;

class Controller extends IlluminateController {

    public function processAsset($path)
    {
        dd($path);
    }

}