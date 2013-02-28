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
     * Output the styles for a given collection.
     *
     * @param  string  $collection
     * @return string
     */
    public function styles($collection)
    {
        return $this->outputCollection($collection, 'styles');
    }

    /**
     * Output the scripts for a given collection.
     *
     * @param  string  $collection
     * @return string
     */
    public function scripts($collection)
    {
        return $this->outputCollection($collection, 'scripts');
    }

    /**
     * Output a given group for a collection.
     *
     * @param  string  $collection
     * @param  string  $group
     * @return string
     */
    public function outputCollection($collection, $group)
    {
        if ( ! isset($this->collections[$collection]))
        {
            return;
        }

        // Get the collection instance from the array of collections. This instance will be used
        // throughout the yielding process to fetch assets and compare manifests.
        $collection = $this->collections[$collection];

        $response = array();

        // Firstly we'll attempt to resolve a fingerprinted collection. If a collection has an
        // existing fingerprint and the application is running within the correct environment
        // we'll fetch the static asset.
        if ($fingerprint = $this->resolver->resolveFingerprintedCollection($collection, $group))
        {
            $response = $this->buildFingerprintedCollection($collection, $fingerprint, $group);
        }

        // Lastly we'll attempt to dynamically route to each of the assets in the collection with
        // a built-in controller.
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

        $path = $this->parseBuildPath("{$collectionName}-{$fingerprint}.{$extension}");

        // We'll get the response of the original fingerprinted collection first. Then we'll need to
        // spin through any of the ignored assets and append them to the response as well. Ignored
        // assets are only ignored by the builder, but they still need to be fetched.
        $response = $this->{'build'.camel_case($group).'Element'}($path);

        return $this->buildIgnoredAssets($collection, $group, array($response));
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
        $name = $collection->getName();

        $assets = $collection->getAssets($group);

        return $this->buildDynamicAssets($name, $group, $assets, array());
    }

    /**
     * Build an array of dynamic assets.
     *
     * @param  string  $name
     * @param  string  $group
     * @param  array  $assets
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
            $path = $asset->getRelativePath();

            if ( ! $asset->isRemote())
            {
                $path = "{$hash}/{$name}/{$path}";
            }

            $key = $asset->getPosition() ?: count($responses) + 1;

            array_splice($responses, $key - 1, 0, array($this->{'build'.camel_case($group).'Element'}($path)));
        }

        return $responses;
    }

    /**
     * Build a collections ignored assets.
     *
     * @param  Basset\Collection  $collection
     * @param  string  $group
     * @param  array  $responses
     * @return array
     */
    protected function buildIgnoredAssets(Collection $collection, $group, array $responses)
    {
        $name = $collection->getName();

        $assets = $collection->getIgnoredAssets($group);

        return $this->buildDynamicAssets($name, $group, $assets, $responses);
    }

    /**
     * Prefix the build path to the existing path if one exists.
     *
     * @param  string  $path
     * @return string
     */
    protected function parseBuildPath($path)
    {
        if ($buildPath = $this->config->get('basset::build_path'))
        {
            $path = "{$buildPath}/{$path}";
        }

        return $path;
    }

    /**
     * Build a stylesheet element for the specified path.
     *
     * @param  string  $path
     * @return string
     */
    protected function buildStylesElement($path)
    {
        return '<link rel="stylesheet" type="text/css" href="'.$this->url->asset($path).'" />';
    }

    /**
     * Build a scripts element for the specified path.
     *
     * @param  string  $path
     * @return string
     */
    protected function buildScriptsElement($path)
    {
        return '<script src="'.$this->url->asset($path).'"></script>';
    }

}