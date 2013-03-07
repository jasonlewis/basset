<?php namespace Basset\Output;

use Basset\Asset;
use Basset\Environment;
use Basset\Collection;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Routing\Controllers\Controller as IlluminateController;

class Controller extends IlluminateController {

    /**
     * Create a new output controller instance.
     *
     * @param  Basset\Environment  $env
     * @return void
     */
    public function __construct(Environment $env)
    {
        $this->env = $env;
    }

    /**
     * Process an asset.
     *
     * @param  string  $path
     * @return string
     */
    public function processAsset($collection, $path)
    {
        if ($this->env->hasCollection($collection))
        {
            $collection = $this->env->collection($collection);

            // Before we attempt to find the asset within the collection any directories
            // need to be processed and filters applied. Then we'll find the asset and return
            // its built response to the browser with the correct headers.
            $collection->processCollection();

            $asset = $this->findAsset($collection, $path);

            if ($asset instanceof Asset)
            {
                return $this->buildResponse($asset);
            }
        }

        throw new NotFoundHttpException("Asset [{$path}] was unable to be processed.");
    }

    /**
     * Build the response for an asset.
     *
     * @param  Basset\Asset  $asset
     * @return Illuminate\Http\Response
     */
    protected function buildResponse(Asset $asset)
    {
        // Build the asset, this applies all filters that have been applied and
        // returns a usable string for responses.
        $contents = $asset->build();

        // Using the assets usable extension we'll determine what the content type
        // for the response should be.
        $contentType = $asset->getUsableExtension() == 'js' ? 'application/javascript' : 'text/css';

        return new Response($contents, 200, array('content-type' => $contentType));
    }

    /**
     * Find an asset in a collection.
     *
     * @param  Basset\Collection  $collection
     * @param  string  $path
     * @return null|Basset\Asset
     */
    protected function findAsset(Collection $collection, $path)
    {
        foreach ($collection->getAssets() as $asset)
        {
            if ($asset->getRelativePath() == $path)
            {
                return $asset;
            }
        }
    }

}