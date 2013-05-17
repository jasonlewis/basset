<?php namespace Basset;

use Basset\Collection;
use Basset\Environment;
use Basset\Manifest\Entry;
use Basset\Manifest\Manifest;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Config\Repository as Config;

class Server {

    /**
     * Basset environment instance.
     * 
     * @var \Basset\Environment
     */
    protected $environment;

    /**
     * Basset manifest instance.
     * 
     * @var \Basset\Manifest\Manifest
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
     * The application working environment.
     * 
     * @var string
     */
    protected $appEnvironment;

    /**
     * Create a new output server instance.
     *
     * @param  \Basset\Environment  $environment
     * @param  \Basset\Manifest\Manifest  $manifest
     * @param  \Illuminate\Config\Repository  $config
     * @param  \Illuminate\Routing\UrlGenerator  $url
     * @param  string  $appEnvironment
     * @return void
     */
    public function __construct(Environment $environment, Manifest $manifest, Config $config, UrlGenerator $url, $appEnvironment)
    {
        $this->environment = $environment;
        $this->manifest = $manifest;
        $this->config = $config;
        $this->url = $url;
        $this->appEnvironment = $appEnvironment;
    }

    /**
     * Serve a collection where the group is determined by the the extension.
     * 
     * @param  string  $collection
     * @param  string  $format
     * @return string
     */
    public function collection($collection, $format = null)
    {
        list($collection, $extension) = preg_split('/\.(css|js)/', $collection, 2, PREG_SPLIT_DELIM_CAPTURE);

        return $this->serve($collection, $extension == 'css' ? 'stylesheets' : 'javascripts', $format);
    }

    /**
     * Serve the stylesheets for a given collection.
     *
     * @param  string  $collection
     * @param  string  $format
     * @return string
     */
    public function stylesheets($collection, $format = null)
    {
        return $this->serve($collection, 'stylesheets', $format);
    }

    /**
     * Serve the javascripts for a given collection.
     *
     * @param  string  $collection
     * @param  string  $format
     * @return string
     */
    public function javascripts($collection, $format = null)
    {
        return $this->serve($collection, 'javascripts', $format);
    }

    /**
     * Serve a given group for a collection.
     *
     * @param  string  $collection
     * @param  string  $group
     * @param  string  $format
     * @return string
     */
    public function serve($collection, $group, $format = null)
    {
        if ( ! isset($this->environment[$collection]))
        {
            return;
        }

        // Get the collection instance from the array of collections. This instance will be used
        // throughout the building process to fetch assets and compare against the stored
        // manfiest of fingerprints.
        $collection = $this->environment[$collection];

        $response = $this->serveExcludedAssets($collection, $group, $format);

        if ($this->manifest->has($collection))
        {
            $entry = $this->manifest->get($collection);

            if ($this->runningInProduction() and $entry->hasProductionFingerprint($group))
            {
                $response = array_merge($response, $this->serveProductionCollection($collection, $entry, $group, $format));
            }
            elseif ($entry->hasDevelopmentAssets($group))
            {
                $response = array_merge($response, $this->serveDevelopmentCollection($collection, $entry, $group, $format));
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
     * @param  string  $format
     * @return array
     */
    protected function serveProductionCollection(Collection $collection, Entry $entry, $group, $format)
    {
        $fingerprint = $entry->getProductionFingerprint($group);

        return array($this->{'create'.studly_case($group).'Element'}($this->prefixBuildPath($fingerprint), $format));
    }

    /**
     * Serve a development collection.
     * 
     * @param  \Basset\Collection  $collection
     * @param  \Basset\Manifest\Entry  $entry
     * @param  string  $group
     * @param  string  $format
     * @return array
     */
    protected function serveDevelopmentCollection(Collection $collection, Entry $entry, $group, $format)
    {
        $responses = array();

        $identifier = $collection->getIdentifier();

        foreach ($entry->getDevelopmentAssets($group) as $path)
        {
            $responses[] = $this->{'create'.studly_case($group).'Element'}($this->prefixBuildPath($identifier.'/'.$path), $format);
        }

        return $responses;
    }

    /**
     * Serve a collections excluded assets.
     *
     * @param  \Basset\Collection  $collection
     * @param  string  $group
     * @param  string  $format
     * @return array
     */
    protected function serveExcludedAssets(Collection $collection, $group, $format)
    {
        $responses = array();

        foreach ($collection->getAssetsOnlyExcluded($group) as $asset)
        {
            $path = $asset->getRelativePath();

            $responses[] = $this->{'create'.studly_case($group).'Element'}($path, $format);
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
     * Determine if the application is running in production mode.
     * 
     * @return bool
     */
    protected function runningInProduction()
    {
        return in_array($this->appEnvironment, (array) $this->config->get('basset::production'));
    }

    /**
     * Create a stylesheets element for the specified path.
     *
     * @param  string  $path
     * @param  string  $format
     * @return string
     */
    protected function createStylesheetsElement($path, $format)
    {
        return sprintf($format ?: '<link rel="stylesheet" type="text/css" href="%s" />', $this->getAssetUrl($path));
    }

    /**
     * Create a javascripts element for the specified path.
     *
     * @param  string  $path
     * @param  string  $format
     * @return string
     */
    protected function createJavascriptsElement($path, $format)
    {
        return sprintf($format ?: '<script src="%s"></script>', $this->getAssetUrl($path));
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

}