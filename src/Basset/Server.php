<?php namespace Basset;

use Basset\Collection;
use Basset\Manifest\Entry;
use Basset\Manifest\Manifest;

class Server {

    /**
     * Laravel application instance.
     * 
     * @var \Illuminate\Foundation\Application
     */
    protected $collections;

    /**
     * Create a new output server instance.
     *
     * @param  \Illuminate\Foundation\Application
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
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
        if ( ! isset($this->app['basset'][$collection]))
        {
            return '<!-- Basset could not find collection: '.$collection.' -->';
        }

        // Get the collection instance from the array of collections. This instance will be used
        // throughout the building process to fetch assets and compare against the stored
        // manfiest of fingerprints.
        $collection = $this->app['basset'][$collection];

        if ($entry = $this->app['basset.manifest']->get($collection))
        {
            if ($this->runningInProduction() and $entry->hasProductionFingerprint($group))
            {
                $response = $this->serveProductionCollection($collection, $entry, $group, $format);
            }
            else
            {
                $response = $this->serveDevelopmentCollection($collection, $entry, $group, $format);
            }

            return array_to_newlines($response);
        }
        else
        {
            return '<!-- Basset could not find manifest entry for collection: '.$collection->getIdentifier().' -->';
        }
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

        $response = $this->serveExcludedAssets($collection, $group, $format);

        return array_merge($response, array($this->{'create'.studly_case($group).'Element'}($this->prefixBuildPath($fingerprint), $format)));
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

        foreach ($collection->getAssetsWithExcluded($group) as $asset)
        {
            if ($asset->isIncluded() and $path = $entry->getDevelopmentAsset($asset))
            {
                $path = $this->prefixBuildPath($identifier.'/'.$path);
            }
            else
            {
                $path = $asset->getRelativePath();
            }

            $responses[] = $this->{'create'.studly_case($group).'Element'}($path, $format);
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
        if ($buildPath = $this->app['config']->get('basset::build_path'))
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
        return in_array($this->app['env'], (array) $this->app['config']->get('basset::production'));
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
        return sprintf($format ?: '<link rel="stylesheet" type="text/css" href="%s" />', $this->buildAssetUrl($path));
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
        return sprintf($format ?: '<script src="%s"></script>', $this->buildAssetUrl($path));
    }

    /**
     * Build the URL to an asset.
     * 
     * @param  string  $path
     * @return string
     */
    public function buildAssetUrl($path)
    {
        return starts_with($path, '//') ? $path : $this->app['url']->asset($path);
    }

}
