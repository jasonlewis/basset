<?php namespace Basset;

use Basset\Filter\Filterable;
use Basset\Factory\FilterFactory;

class Collection extends Filterable {

    /**
     * The collection identifier.
     *
     * @var string
     */
    protected $identifier;

    /**
     * The default directory of the collection.
     * 
     * @var \Basset\Directory
     */
    protected $directory;

    /**
     * Create a new collection instance.
     *
     * @param  string  $identifier
     * @param  \Basset\Directory  $directory
     * @return void
     */
    public function __construct($identifier, Directory $directory, FilterFactory $filterFactory)
    {
        $this->identifier = $identifier;
        $this->directory = $directory;
        $this->filterFactory = $filterFactory;
        $this->filters = new \Illuminate\Support\Collection;
    }

    /**
     * Get all the assets filtered by a group and without the excluded assets.
     * 
     * @param  string  $group
     * @return \Illuminate\Support\Collection
     */
    public function getAssetsWithoutExcluded($group = null)
    {
        return $this->getAssets($group, false);
    }

    /**
     * Get all the assets filtered by a group and with the excluded assets.
     *
     * @param  string  $group
     * @return \Illuminate\Support\Collection
     */
    public function getAssetsWithExcluded($group = null)
    {
        return $this->getAssets($group, true);
    }

    /**
     * Get all the assets filtered by a group but only if the assets are excluded.
     *
     * @param  string  $group
     * @return \Illuminate\Support\Collection
     */
    public function getAssetsOnlyExcluded($group = null)
    {
        // Get all the assets for the given group and filter out assets that aren't listed
        // as being excluded.
        $assets = $this->getAssets($group, true)->filter(function($asset)
        {
            return $asset->isExcluded();
        });

        return $assets;
    }

    /**
     * Get all the assets filtered by a group and if to include the excluded assets.
     *
     * @param  string  $group
     * @param  bool  $excluded
     * @return \Illuminate\Support\Collection
     */
    public function getAssets($group = null, $excluded = true)
    {
        // Spin through all of the assets that belong to the given group and push them on
        // to the end of the array.
        $assets = clone $this->directory->getAssets();

        foreach ($assets as $key => $asset)
        {
            if ( ! $excluded and $asset->isExcluded() or ! is_null($group) and ! $asset->{'is'.ucfirst(str_singular($group))}())
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
     * Get the identifier of the collection.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
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