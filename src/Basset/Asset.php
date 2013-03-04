<?php namespace Basset;

use Closure;
use InvalidArgumentException;
use Assetic\Asset\StringAsset;
use Assetic\Filter\FilterInterface;
use Illuminate\Filesystem\Filesystem;

class Asset implements FilterableInterface {

    /**
     * Illuminate filesystem instance.
     *
     * @var Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Filter factory instance.
     *
     * @var Basset\FilterFactory
     */
    protected $filterFactory;

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
     * Position of the asset.
     *
     * @var int
     */
    protected $position;

    /**
     * Create a new asset instance.
     *
     * @param  Illuminate\Filesystem\Filesystem  $files
     * @param  Basset\FilterFactory  $filterFactory
     * @param  string  $absolutePath
     * @param  string  $relativePath
     * @param  string  $appEnvironment
     * @return void
     */
    public function __construct(Filesystem $files, FilterFactory $filterFactory, $absolutePath, $relativePath, $appEnvironment)
    {
        $this->files = $files;
        $this->filterFactory = $filterFactory;
        $this->absolutePath = $absolutePath;
        $this->relativePath = $relativePath;
        $this->appEnvironment = $appEnvironment;
    }

    /**
     * Get the usable extension of the asset.
     *
     * @return string
     */
    public function getUsableExtension()
    {
        return $this->isScript() ? 'js' : 'css';
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
     * Determine if asset is a script.
     *
     * @return bool
     */
    public function isScript()
    {
        return in_array(pathinfo($this->absolutePath, PATHINFO_EXTENSION), array('js', 'coffee'));
    }

    /**
     * Determine if asset is a style.
     *
     * @return bool
     */
    public function isStyle()
    {
        return ! $this->isScript();
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
     * Alias for Basset\Asset::setPosition(1)
     *
     * @return Basset\Asset
     */
    public function first()
    {
        return $this->setPosition(1);
    }

    /**
     * Alias for Basset\Asset::setPosition(2)
     *
     * @return Basset\Asset
     */
    public function second()
    {
        return $this->setPosition(2);
    }

    /**
     * Alias for Basset\Asset::setPosition(3)
     *
     * @return Basset\Asset
     */
    public function third()
    {
        return $this->setPosition(3);
    }

    /**
     * Alias for Basset\Asset::setPosition()
     *
     * @param  int  $position
     * @return Basset\Asset
     */
    public function position($position)
    {
        return $this->setPosition($position);
    }

    /**
     * Set the position of the outputted asset.
     *
     * @param  int  $position
     * @return Basset\Asset
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get the assets position.
     *
     * @return int|null
     */
    public function getPosition()
    {
        return $this->position;
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
        return $this->isScript() ? 'scripts' : 'styles';
    }

    /**
     * Prepare the assets filters by removing those that have been restricted.
     *
     * @return void
     */
    public function prepareFilters()
    {
        foreach ($this->filters as $key => $filter)
        {
            // If there is a group restriction on the filter and the assets group is not that being
            // restricted then we'll remove the filter from the asset.
            $groupRestriction = $filter->getGroupRestriction();

            if ($groupRestriction and ! $this->{'is'.ucfirst(str_singular($groupRestriction))}())
            {
                unset($this->filters[$key]);
            }

            // If the filter is being restricted to certain environments we'll make sure the application
            // is running within one of the specified environments.
            $possibleEnvironments = $filter->getEnvironments();

            if ($possibleEnvironments and ! in_array($this->appEnvironment, $possibleEnvironments))
            {
                unset($this->filters[$key]);
            }
        }
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
        $filter = $this->filterFactory->make($filter, $callback, $this);

        $key = $filter->getFilter();

        return $this->filters[$key] = $filter;
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
        // Before we begin to build the asset we'll prepare the filters by removing those that are not
        // to be applied to this asset.
        $this->prepareFilters();

        $appliedFilters = array();

        foreach ($this->filters as $filter)
        {
            // Attempt to instantiate each filter. If we can get an instance we'll add the filter
            // to the array of filters.
            $filterInstance = $filter->instantiate();

            if ($filterInstance instanceof FilterInterface)
            {
                $appliedFilters[] = $filterInstance;
            }
        }

        $asset = new StringAsset($this->getContent(), $appliedFilters, dirname($this->absolutePath), basename($this->absolutePath));

        return $asset->dump();
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
     * Dynamically handle the "include" method as we can't set the method on the class.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ($method == 'include')
        {
            return $this->setIncluded(true);
        }

        throw new InvalidArgumentException("Call to undefined method [{$method}] on Basset\Asset.");
    }

}