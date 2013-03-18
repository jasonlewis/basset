<?php namespace Basset\Output;

use Basset\Asset;
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
    public function __construct(Resolver $resolver, Repository $config, Store $session, UrlGenerator $url, array $collections = array())
    {
        $this->resolver = $resolver;
        $this->config = $config;
        $this->session = $session;
        $this->url = $url;
        $this->collections = $collections;
    }

    /**
     * Set the collections on the server.
     * 
     * @param  array  $collections
     * @return Basset\Output\Server
     */
    public function setCollections(array $collections)
    {
        $this->collections = array_merge($this->collections, $collections);

        return $this;
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

        $this->resolver->setCollection($collection);

        // Firstly we'll attempt to resolve a fingerprinted collection. If a collection has an
        // existing fingerprint and the application is running within the correct environment
        // we'll fetch the static asset.
        if ($fingerprint = $this->resolver->resolveFingerprintedCollection($group))
        {
            $response = $this->serveFingerprintedCollection($collection, $fingerprint, $group);
        }

        elseif ($development = $this->resolver->resolveDevelopmentCollection($group))
        {
            $response = $this->serveDevelopmentCollection($collection, $development, $group); 
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

        $buildPath = $this->prefixBuildPath("{$collectionName}-{$fingerprint}.{$extension}");

        // We'll get the response of the original fingerprinted collection first. Then we'll need to
        // spin through any of the excluded assets and append them to the response as well. Excluded
        // assets are only excluded by the builder, but they still need to be fetched.
        $response = $this->{'create'.studly_case($group).'Element'}($buildPath);

        return $this->serveExcludedAssets($collection, $group, array($response));
    }

    /**
     * Serve a development collection.
     * 
     * @param  Basset\Collection  $collection
     * @param  array  $development
     * @param  string  $group
     * @return array
     */
    protected function serveDevelopmentCollection(Collection $collection, array $development, $group)
    {
        $collectionName = $collection->getName();

        // Spin through every asset within the specified group for the collection and attempt to
        // locate each one within the array of development assets. We'll build an array of
        // HTML elements to dump at the end.
        $responses = array();

        foreach ($collection->getAssets($group) as $asset)
        {
            $relativePath = $asset->getRelativePath();

            // If the relative path to the asset is not in the array of development assets then the
            // asset is not to be included during the serving process.
            if ( ! array_key_exists($relativePath, $development))
            {
                continue;
            }

            // Get the build path from the array of development assets. If the asset is not a remotely
            // hosted asset and it's to be included we'll prefix the configuration build path.
            $buildPath = $development[$relativePath];

            if ( ! $asset->isRemote() and $asset->isIncluded())
            {
                $buildPath = $this->prefixBuildPath("{$collectionName}/{$buildPath}");
            }

            $this->orderAssetResponses($asset, $responses, $this->{'create'.studly_case($group).'Element'}($buildPath));
        }

        return $responses;
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

            $this->orderAssetResponses($asset, $responses, $this->{'create'.studly_case($group).'Element'}($path));
        }

        return $responses;
    }

    /**
     * Order the asset in the array of responses.
     * 
     * @param  Basset\Asset  $asset
     * @param  array  $responses
     * @param  string  $element
     * @return void
     */
    protected function orderAssetResponses(Asset $asset, array &$responses, $element)
    {
        is_numeric($order = $asset->getOrder()) and $order--;

        // If the order we have is not numeric then we need to guess the order of the asset by
        // spinning through each of the current existing responses and finding an empty spot
        // before the current response. Otherwise we'll just add the asset to the end.
        if ( ! is_numeric($order))
        {
            $order = count($responses);

            foreach ($responses as $key => $value)
            {
                if ($key > 0 and ! array_key_exists($key - 1, $responses))
                {
                    $order = $key - 1;

                    break;
                }
            }
        }

        // If the position already exists then we'll insert a new empty element into the array
        // at that given position so that we don't overwrite any existing elements.
        if (array_key_exists($order, $responses))
        {
            array_splice($responses, $order, 0, array(null));
        }

        $responses[$order] = $element;

        // After the element has been added to the array of responses we'll sort the array by
        // its keys so that any elements given a lower order ranking are ordered in their
        // correct positions.
        ksort($responses);
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
     * Prefix the build path to a given path.
     * 
     * @param  string  $path
     * @return string
     */
    protected function prefixBuildPath($path)
    {
        if ($buildPath = $this->config->get('basset::build_path'))
        {
            $path = "{$buildPath}/{$path}";
        }

        return $path;
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

    /**
     * Get the output resolver instance.
     * 
     * @return Basset\Output\Resolver
     */
    public function getResolver()
    {
        return $this->resolver;
    }

    /**
     * Get the illuminate config repository instance.
     * 
     * @return Illuminate\Config\Repository
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get the illuminate session store instance.
     * 
     * @return Illuminate\Session\Store
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Get the illuminate url generator instance.
     * 
     * @return Illuminate\Routing\UrlGenerator
     */
    public function getUrl()
    {
        return $this->url;
    }

}