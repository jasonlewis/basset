<?php namespace Basset\Output;

use Basset\Collection;
use Illuminate\Session\Store;
use Illuminate\Config\Repository;
use Illuminate\Routing\UrlGenerator;
use Basset\BassetServiceProvider as Provider;

class Server {

    /**
     * Output resolver instance.
     *
     * @var Basset\Output\Resolver
     */
    protected $resolver;

    /**
     * Illuminate config repository instance.
     *
     * @var Illuminate\Config\Repository
     */
    protected $config;

    /**
     * Illuminate session store instance.
     *
     * @var Illuminate\Session\Store
     */
    protected $session;

    /**
     * Illuminate url generator instance.
     *
     * @var Illuminate\Routing\UrlGenerator
     */
    protected $url;

    /**
     * Array of asset collections.
     *
     * @var array
     */
    protected $collections;

    /**
     * Create a new output server instance.
     *
     * @param  Basset\Output\Resolver  $resolver
     * @param  Illuminate\Config\Repository  $config
     * @param  Illuminate\Session\Store  $session
     * @param  Illuminate\Routing\UrlGenerator  $url
     * @param  array  $collections
     * @return void
     */
    public function __construct(Resolver $resolver, Repository $config, Store $session, UrlGenerator $url, array $collections)
    {
        $this->resolver = $resolver;
        $this->config = $config;
        $this->session = $session;
        $this->url = $url;
        $this->collections = $collections;
    }

    /**
     * Serve the stylesheets for a given collection.
     *
     * @param  string  $collection
     * @return string
     */
    public function stylesheets($collection)
    {
        return $this->serveCollection($collection, 'stylesheets');
    }

    /**
     * Serve the javascripts for a given collection.
     *
     * @param  string  $collection
     * @return string
     */
    public function javascripts($collection)
    {
        return $this->serveCollection($collection, 'javascripts');
    }

    /**
     * Serve a given group for a collection.
     *
     * @param  string  $collection
     * @param  string  $group
     * @return string
     */
    public function serveCollection($collection, $group)
    {
        if ( ! isset($this->collections[$collection]))
        {
            return;
        }

        // Get the collection instance from the array of collections. This instance will be used
        // throughout the building process to fetch assets and compare against the stored
        // manfiest of fingerprints.
        $collection = $this->collections[$collection];

        // Firstly we'll attempt to resolve a fingerprinted collection. If a collection has an
        // existing fingerprint and the application is running within the correct environment
        // we'll fetch the static asset.
        if ($fingerprint = $this->resolver->resolveFingerprintedCollection($collection, $group))
        {
            $response = $this->serveFingerprintedCollection($collection, $fingerprint, $group);
        }

        // Lastly we'll dynamically serve each of the assets within the collection by using
        // an internal controller to process and build each asset. This is fine during
        // development, although it may impact page load times.
        else
        {
            $response = $this->serveDynamicCollection($collection, $group);
        }

        return array_to_newlines($response);
    }

    /**
     * Serve a fingerprinted collection.
     *
     * @param  Basset\Collection  $collection
     * @param  string  $fingerprint
     * @param  string  $group
     * @return array
     */
    protected function serveFingerprintedCollection(Collection $collection, $fingerprint, $group)
    {
        $collectionName = $collection->getName();

        $extension = $collection->determineExtension($group);

        $path = "{$collectionName}-{$fingerprint}.{$extension}";

        if ($buildPath = $this->config->get('basset::build_path'))
        {
            $path = "{$buildPath}/{$path}";
        }

        // We'll get the response of the original fingerprinted collection first. Then we'll need to
        // spin through any of the excluded assets and append them to the response as well. Excluded
        // assets are only excluded by the builder, but they still need to be fetched.
        $response = $this->{'create'.studly_case($group).'Element'}($path);

        return $this->serveExcludedAssets($collection, $group, array($response));
    }

    /**
     * Serve a dynamic collection.
     *
     * @param  Basset\Collection  $collection
     * @param  string  $group
     * @return array
     */
    protected function serveDynamicCollection(Collection $collection, $group)
    {
        return $this->serveDynamicAssets($collection->getName(), $group, $collection->getAssets($group), array());
    }

    /**
     * Serve an array of dynamic assets.
     *
     * @param  string  $name
     * @param  string  $group
     * @param  array  $assets
     * @param  array  $responses
     * @return array
     */
    protected function serveDynamicAssets($name, $group, array $assets, array $responses)
    {
        // The path to dynamically generated assets includes a random hash that's been stored in each
        // session. We'll prefix assets that aren't remotely hosted with this hash.
        $hash = $this->session->get(Provider::SESSION_HASH);

        foreach ($assets as $asset)
        {
            $path = $asset->getUsablePath();

            if ( ! $asset->isRemote())
            {
                $path = "{$hash}/{$name}/{$path}";
            }

            $key = $asset->getOrder() ?: count($responses) + 1;

            array_splice($responses, $key - 1, 0, array($this->{'create'.studly_case($group).'Element'}($path)));
        }

        return $responses;
    }

    /**
     * Serve a collections excluded assets.
     *
     * @param  Basset\Collection  $collection
     * @param  string  $group
     * @param  array  $responses
     * @return array
     */
    protected function serveExcludedAssets(Collection $collection, $group, array $responses)
    {
        return $this->serveDynamicAssets($collection->getName(), $group, $collection->getExcludedAssets($group), $responses);
    }

    /**
     * Create a stylesheets element for the specified path.
     *
     * @param  string  $path
     * @return string
     */
    protected function createStylesheetsElement($path)
    {
        return '<link rel="stylesheet" type="text/css" href="'.$this->url->asset($path).'" />';
    }

    /**
     * Create a javascripts element for the specified path.
     *
     * @param  string  $path
     * @return string
     */
    protected function createJavascriptsElement($path)
    {
        return '<script src="'.$this->url->asset($path).'"></script>';
    }

}