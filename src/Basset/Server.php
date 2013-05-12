<?php namespace Basset;

use Basset\Collection;
use Basset\Environment;
use Basset\Manifest\Entry;
use Illuminate\Routing\UrlGenerator;
use Basset\Manifest\Repository as Manifest;
use Illuminate\Config\Repository as Config;

class Server {

    /**
     * Basset environment instance.
     * 
     * @var \Basset\Environment
     */
    protected $environment;

    /**
     * Basset manifest repository instance.
     * 
     * @var \Basset\Manifest\Repository
     */
    protected $manifest;

    /**
     * Illuminate config repository instance.
     *
     * @var \Illuminate\Config\Repository
     */
    protected $config;

    /**
     * Illuminate url generator instance.
     *
     * @var \Illuminate\Routing\UrlGenerator
     */
    protected $url;

    /**
     * Create a new output server instance.
     *
     * @param  \Basset\Environment  $environment
     * @param  \Basset\Manifest\Repository  $manifest
     * @param  \Illuminate\Config\Repository  $config
     * @param  \Illuminate\Routing\UrlGenerator  $url
     * @return void
     */
    public function __construct(Environment $environment, Manifest $manifest, Config $config, UrlGenerator $url)
    {
        $this->environment = $environment;
        $this->manifest = $manifest;
        $this->config = $config;
        $this->url = $url;
    }

    /**
     * Serve a collection where the group is determined by the the extension.
     * 
     * @param  string  $collection
     * @return string
     */
    public function collection($collection)
    {
        list($collection, $extension) = preg_split('/\.(css|js)/', $collection, 2, PREG_SPLIT_DELIM_CAPTURE);

        return $this->serve($collection, $extension == 'css' ? 'stylesheets' : 'javascripts');
    }

    /**
     * Serve the stylesheets for a given collection.
     *
     * @param  string  $collection
     * @return string
     */
    public function stylesheets($collection)
    {
        return $this->serve($collection, 'stylesheets');
    }

    /**
     * Serve the javascripts for a given collection.
     *
     * @param  string  $collection
     * @return string
     */
    public function javascripts($collection)
    {
        return $this->serve($collection, 'javascripts');
    }

    /**
     * Serve a given group for a collection.
     *
     * @param  string  $collection
     * @param  string  $group
     * @return string
     */
    public function serve($collection, $group)
    {
        if ( ! isset($this->environment[$collection]))
        {
            return;
        }

        // Get the collection instance from the array of collections. This instance will be used
        // throughout the building process to fetch assets and compare against the stored
        // manfiest of fingerprints.
        $collection = $this->environment[$collection];
        
        $response = array();

        if ($this->manifest->has($collection))
        {
            $entry = $this->manifest->get($collection);

            if ($this->environment->runningInProduction() and $entry->hasProductionFingerprint($group))
            {
                $response = $this->serveProductionCollection($collection, $entry, $group);
            }
            elseif ($entry->hasDevelopmentAssets($group))
            {
                $response = $this->serveDevelopmentCollection($collection, $entry, $group);
            }
        }

        return array_to_newlines($response);
    }

    /**
     * Serve a production collection.
     *
     * @param  \Basset\Collection  $collection
     * @param  \Basset\Manifest\Entry  $entry
     * @param  string  $group
     * @return array
     */
    protected function serveProductionCollection(Collection $collection, Entry $entry, $group)
    {
        $responses = $this->serveExcludedAssets($collection, $group);

        $fingerprint = $entry->getProductionFingerprint($group);

        $responses[] = $this->{'create'.studly_case($group).'Element'}($this->prefixBuildPath($fingerprint));

        return $responses;
    }

    /**
     * Serve a development collection.
     * 
     * @param  \Basset\Collection  $collection
     * @param  \Basset\Manifest\Entry  $entry
     * @param  string  $group
     * @return array
     */
    protected function serveDevelopmentCollection(Collection $collection, Entry $entry, $group)
    {
        $responses = $this->serveExcludedAssets($collection, $group);

        foreach ($entry>getDevelopmentAssets($group) as $path)
        {
            $responses[] = $this->{'create'.studly_case($group).'Element'}($this->prefixBuildPath($collection->getName().'/'.$path));
        }

        return $responses;
    }

    /**
     * Serve a collections excluded assets.
     *
     * @param  \Basset\Collection  $collection
     * @param  string  $group
     * @return array
     */
    protected function serveExcludedAssets(Collection $collection, $group)
    {
        $responses = array();

        foreach ($collection->getExcludedAssets($group) as $asset)
        {
            $path = $asset->getRelativePath();

            if ( ! $asset->isRemote())
            {
                $path = $this->prefixBuildPath($path);
            }

            $responses[] = $this->{'create'.studly_case($group).'Element'}($path);
        }

        return $responses;
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
        return '<link rel="stylesheet" type="text/css" href="'.$this->getAssetUrl($path).'" />';
    }

    /**
     * Create a javascripts element for the specified path.
     *
     * @param  string  $path
     * @return string
     */
    protected function createJavascriptsElement($path)
    {
        return '<script src="'.$this->getAssetUrl($path).'"></script>';
    }

    /**
     * Get the URL to an asset.
     * 
     * @param  string  $path
     * @return string
     */
    public function getAssetUrl($path)
    {
        return starts_with($path, '//') ? $path : $this->url->asset($path);
    }

    /**
     * Get the illuminate config repository instance.
     * 
     * @return \Illuminate\Config\Repository
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get the illuminate url generator instance.
     * 
     * @return \Illuminate\Routing\UrlGenerator
     */
    public function getUrlGenerator()
    {
        return $this->url;
    }

    /**
     * Get the basset manifest repository instance.
     * 
     * @return \Basset\Manifest\Repository
     */
    public function getManifest()
    {
        return $this->manifest;
    }

    /**
     * Get the basset environment instance.
     * 
     * @return \Basset\Environment
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

}