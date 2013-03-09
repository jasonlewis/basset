<?php namespace Basset\Output;

use Basset\Collection;
use Illuminate\Session\Store;
use Illuminate\Config\Repository;
use Illuminate\Routing\UrlGenerator;

class Builder {

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
     * Create a new output builder instance.
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
     * Build the stylesheets for a given collection.
     *
     * @param  string  $collection
     * @return string
     */
    public function stylesheets($collection)
    {
        return $this->buildCollection($collection, 'stylesheets');
    }

    /**
     * Build the javascripts for a given collection.
     *
     * @param  string  $collection
     * @return string
     */
    public function javascripts($collection)
    {
        return $this->buildCollection($collection, 'javascripts');
    }

    /**
     * Build a given group for a collection.
     *
     * @param  string  $collection
     * @param  string  $group
     * @return string
     */
    public function buildCollection($collection, $group)
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
            $response = $this->buildFingerprintedCollection($collection, $fingerprint, $group);
        }

        // Lastly we'll dynamically build each of the assets within the collection by using
        // an internal controller to process and build each asset. This is fine during
        // development, although it may impact page load times.
        else
        {
            $response = $this->buildDynamicCollection($collection, $group);
        }

        return array_to_newlines($response);
    }

    /**
     * Build a fingerprinted collection.
     *
     * @param  Basset\Collection  $collection
     * @param  string  $fingerprint
     * @param  string  $group
     * @return array
     */
    protected function buildFingerprintedCollection(Collection $collection, $fingerprint, $group)
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
        $response = $this->{'build'.studly_case($group).'Element'}($path);

        return $this->buildExcludedAssets($collection, $group, array($response));
    }

    /**
     * Build a dynamic collection.
     *
     * @param  Basset\Collection  $collection
     * @param  string  $group
     * @return array
     */
    protected function buildDynamicCollection(Collection $collection, $group)
    {
        return $this->buildDynamicAssets($collection->getName(), $group, $collection->getAssets($group), array());
    }

    /**
     * Build an array of dynamic assets.
     *
     * @param  string  $name
     * @param  string  $group
     * @param  array  $assets
     * @param  array  $responses
     * @return array
     */
    protected function buildDynamicAssets($name, $group, array $assets, array $responses)
    {
        // The path to dynamically generated assets includes a random hash that's been
        // stored in each session. We'll prefix assets that aren't remotely hosted with
        // this hash.
        $hash = $this->session->get('basset_hash');

        foreach ($assets as $asset)
        {
            $path = $asset->getUsablePath();

            if ( ! $asset->isRemote())
            {
                $path = "{$hash}/{$name}/{$path}";
            }

            $key = $asset->getPosition() ?: count($responses) + 1;

            array_splice($responses, $key - 1, 0, array($this->{'build'.studly_case($group).'Element'}($path)));
        }

        return $responses;
    }

    /**
     * Build a collections excluded assets.
     *
     * @param  Basset\Collection  $collection
     * @param  string  $group
     * @param  array  $responses
     * @return array
     */
    protected function buildExcludedAssets(Collection $collection, $group, array $responses)
    {
        return $this->buildDynamicAssets($collection->getName(), $group, $collection->getExcludedAssets($group), $responses);
    }

    /**
     * Build a stylesheets element for the specified path.
     *
     * @param  string  $path
     * @return string
     */
    protected function buildStylesheetsElement($path)
    {
        return '<link rel="stylesheet" type="text/css" href="'.$this->url->asset($path).'" />';
    }

    /**
     * Build a javascripts element for the specified path.
     *
     * @param  string  $path
     * @return string
     */
    protected function buildJavascriptsElement($path)
    {
        return '<script src="'.$this->url->asset($path).'"></script>';
    }

}