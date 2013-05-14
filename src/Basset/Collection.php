<?php namespace Basset;

use Basset\Filter\Filterable;
use Basset\Factory\FilterFactory;

class Collection extends Filterable {

    /**
     * Name of collection.
     *
     * @var string
     */
    protected $name;

    /**
     * The default directory of the collection.
     * 
     * @var \Basset\Directory
     */
    protected $directory;

    /**
     * Create a new collection instance.
     *
     * @param  string  $name
     * @param  \Basset\Directory  $directory
     * @return void
     */
    public function __construct($name, Directory $directory, FilterFactory $filterFactory)
    {
        $this->name = $name;
        $this->directory = $directory;
        $this->filterFactory = $filterFactory;
        $this->filters = new \Illuminate\Support\Collection;
    }

    /**
     * Get an array of assets filtered by a group.
     *
     * @param  string  $group
     * @return \Illuminate\Support\Collection
     */
    public function getAssets($group = null)
    {
        // Spin through all of the assets that belong to the given group and push them on
        // to the end of the array.
        $assets = $this->directory->getAssets();

        foreach ($assets as $key => $asset)
        {
            if ( ! is_null($group) and ! $asset->{'is'.ucfirst(str_singular($group))}())
            {
                $assets->forget($key);
            }
        }

        // Spin through each of the assets and build an ordered array of assets. Once
        // we have the ordered array we'll transform it into a collection and apply
        // the collection wide filters to each asset.
        $ordered = array();

        foreach ($assets as $asset)
        {
            $this->orderAsset($asset, $ordered);
        }

        $ordered = new \Illuminate\Support\Collection($ordered);

        $this->filters->each(function($filter) use (&$ordered)
        {
            $ordered->each(function($asset) use ($filter) { $asset->apply($filter); });
        });

        return $ordered;
    }

    /**
     * Get an array of excluded assets filtered by a group.
     *
     * @param  string  $group
     * @return \Illuminate\Support\Collection
     */
    public function getExcludedAssets($group = null)
    {
        // Get all the assets for the given group and filter out assets that aren't listed
        // as being excluded.
        $assets = $this->getAssets($group)->filter(function($asset)
        {
            return $asset->isExcluded();
        });

        return $assets;
    }

    /**
     * Orders the array of assets as they were defined or on a user ordered basis.
     * 
     * @param  \Basset\Asset  $asset
     * @param  array  $assets
     * @return void
     */
    protected function orderAsset(Asset $asset, array &$assets)
    {
        $order = $asset->getOrder() and $order--;

        // If an asset already exists at the given order key then we'll add one to the order
        // so the asset essentially appears after the existing asset. This makes sense since
        // the array of assets has been reversed, so if the last asset was told to be first
        // then when we finally get to the first added asset it's added second.
        if (array_key_exists($order, $assets))
        {
            array_splice($assets, $order, 0, array(null));
        }

        $assets[$order] = $asset;

        ksort($assets);
    }

    /**
     * Get the name of the collection.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the default directory.
     * 
     * @return \Basset\Directory
     */
    public function getDefaultDirectory()
    {
        return $this->directory;
    }

    /**
     * Determine an extension based on the group.
     *
     * @param  string  $group
     * @return string
     */
    public function getExtension($group)
    {
        return str_plural($group) == 'stylesheets' ? 'css' : 'js';
    }

    /**
     * Dynamically call methods on the default directory.
     * 
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array(array($this->directory, $method), $parameters);
    }

}