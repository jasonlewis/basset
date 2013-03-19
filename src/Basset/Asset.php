<?php namespace Basset;

use Closure;
use Basset\Factory\Manager;
use InvalidArgumentException;
use Assetic\Asset\StringAsset;
use Assetic\Filter\FilterInterface;
use Illuminate\Filesystem\Filesystem;
use Basset\Filter\FilterableInterface;

class Asset implements FilterableInterface {

    /**
     * Illuminate filesystem instance.
     *
     * @var Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Factory manager instance.
     *
     * @var Basset\Factory\Manager
     */
    protected $factory;

    /**
     * Absolute path to the asset.
     *
     * @var string
     */
    protected $absolutePath;

    /**
     * Relative path to the asset.
     *
     * @var string
     */
    protected $relativePath;

    /**
     * Application working environment.
     *
     * @var string
     */
    protected $appEnvironment;

    /**
     * Array of filters.
     *
     * @var array
     */
    protected $filters = array();

    /**
     * Indicates if the asset is to be excluded.
     *
     * @var bool
     */
    protected $excluded = false;

    /**
     * Order of the asset.
     *
     * @var int
     */
    protected $order;

    /**
     * Create a new asset instance.
     *
     * @param  Illuminate\Filesystem\Filesystem  $files
     * @param  Basset\Factory\Manager  $factory
     * @param  string  $absolutePath
     * @param  string  $relativePath
     * @param  string  $appEnvironment
     * @return void
     */
    public function __construct(Filesystem $files, Manager $factory, $absolutePath, $relativePath, $appEnvironment)
    {
        $this->files = $files;
        $this->factory = $factory;
        $this->absolutePath = $absolutePath;
        $this->relativePath = $relativePath;
        $this->appEnvironment = $appEnvironment;
    }

    /**
     * Get the usable path of the asset.
     * 
     * @return string
     */
    public function getUsablePath()
    {
        $extension = pathinfo($path = $this->getRelativePath(), PATHINFO_EXTENSION);

        $path = strstr($path, ".{$extension}", true);

        return "{$path}.{$this->getUsableExtension()}";
    }

    /**
     * Get the usable extension of the asset.
     *
     * @return string
     */
    public function getUsableExtension()
    {
        return $this->isJavascript() ? 'js' : 'css';
    }

    /**
     * Get the absolute path to the asset.
     *
     * @return string
     */
    public function getAbsolutePath()
    {
        return $this->absolutePath;
    }

    /**
     * Get the relative path to the asset.
     *
     * @return string
     */
    public function getRelativePath()
    {
        return $this->relativePath;
    }

    /**
     * Determine if asset is a javascript.
     *
     * @return bool
     */
    public function isJavascript()
    {
        return in_array(pathinfo($this->absolutePath, PATHINFO_EXTENSION), array('js', 'coffee'));
    }

    /**
     * Determine if asset is a stylesheet.
     *
     * @return bool
     */
    public function isStylesheet()
    {
        return ! $this->isJavascript();
    }

    /**
     * Determine if asset is remotely hosted.
     *
     * @return bool
     */
    public function isRemote()
    {
        return (bool) filter_var($this->absolutePath, FILTER_VALIDATE_URL);
    }

    /**
     * Alias for Basset\Asset::setOrder(1)
     *
     * @return Basset\Asset
     */
    public function first()
    {
        return $this->setOrder(1);
    }

    /**
     * Alias for Basset\Asset::setOrder(2)
     *
     * @return Basset\Asset
     */
    public function second()
    {
        return $this->setOrder(2);
    }

    /**
     * Alias for Basset\Asset::setOrder(3)
     *
     * @return Basset\Asset
     */
    public function third()
    {
        return $this->setOrder(3);
    }

    /**
     * Alias for Basset\Asset::setOrder()
     *
     * @param  int  $order
     * @return Basset\Asset
     */
    public function order($order)
    {
        return $this->setOrder($order);
    }

    /**
     * Set the order of the outputted asset.
     *
     * @param  int  $order
     * @return Basset\Asset
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get the assets order.
     *
     * @return int|null
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Alias for Basset\Asset::setExcluded(true)
     *
     * @return Basset\Asset
     */
    public function exclude()
    {
        return $this->setExcluded(true);
    }

    /**
     * Sets the asset to be excluded.
     *
     * @param  bool  $excluded
     * @return Basset\Asset
     */
    public function setExcluded($excluded)
    {
        $this->excluded = $excluded;

        return $this;
    }

    /**
     * Determine if the asset is excluded.
     *
     * @return bool
     */
    public function isExcluded()
    {
        return $this->excluded;
    }

    /**
     * Sets the asset to be included.
     *
     * @param  bool  $included
     * @return Basset\Asset
     */
    public function setIncluded($included)
    {
        $this->excluded = ! $included;

        return $this;
    }

    /**
     * Determine if the asset is included.
     *
     * @return bool
     */
    public function isIncluded()
    {
        return ! $this->excluded;
    }

    /**
     * Get the assets group.
     *
     * @return string
     */
    public function getGroup()
    {
        return $this->isJavascript() ? 'javascripts' : 'stylesheets';
    }

    /**
     * Apply a filter to the asset.
     *
     * @param  string|Filter  $filter
     * @param  Closure  $callback
     * @return Basset\Filter
     */
    public function apply($filter, Closure $callback = null)
    {
        $instance = $this->factory['filter']->make($filter);

        $instance->setResource($this)->runCallback($callback);

        return $this->filters[$instance->getFilter()] = $instance;
    }

    /**
     * Get the asset contents.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->files->getRemote($this->absolutePath);
    }

    /**
     * Build the asset.
     *
     * @return string
     */
    public function build()
    {
        // Spin through each of the applied assets and remove any where we don't get a class
        // that does not implement Assetic\Filter\FilterInterface.
        $filters = array();

        foreach ($this->filters as $key => $filter)
        {
            $instance = $filter->getInstance();

            if ( ! $instance instanceof FilterInterface)
            {
                unset($this->filters[$key]);

                continue;
            }

            $filters[] = $instance;
        }

        $asset = new StringAsset($this->getContent(), $filters, dirname($this->absolutePath), basename($this->absolutePath));

        return $asset->dump();
    }

    /**
     * Get the application environment.
     * 
     * @return string
     */
    public function getAppEnvironment()
    {
        return $this->appEnvironment;
    }

    /**
     * Get the applied filters.
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Get factory manager instance.
     * 
     * @return Basset\Factory\Manager
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * Get illuminate filesystem instance.
     * 
     * @return Illuminate\Filesystem\Filesystem
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Dynamically handle the "include" method as we can't set the method on the class.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // Because PHP doesn't allow us to name a method as "include" we'll revert to magically
        // capturing it when the method can't be resolved. If the method is "include" then we'll
        // simply set the asset to be included.
        if ($method == 'include')
        {
            return $this->setIncluded(true);
        }

        throw new InvalidArgumentException("Call to undefined method [{$method}] on Basset\Asset.");
    }

}