<?php namespace Basset;

use Basset\Factory\FilterFactory;
use Basset\Filter\Filterable;
use InvalidArgumentException;
use Assetic\Asset\StringAsset;
use Assetic\Filter\FilterInterface;
use Illuminate\Filesystem\Filesystem;

class Asset extends Filterable {

    /**
     * Illuminate filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Basset filter factory instance.
     *
     * @var Basset\Factory\FilterFactory
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
     * Assets cached last modified time.
     * 
     * @var int
     */
    protected $lastModified;

    /**
     * Group the asset belongs to, either stylesheets or javascripts.
     * 
     * @var string
     */
    protected $group;

    /**
     * Array of allowed asset extensions.
     * 
     * @var array
     */
    protected $allowedExtensions = array(
        'stylesheets' => array('css', 'sass', 'scss', 'less', 'styl', 'roo', 'gss'),
        'javascripts' => array('js', 'coffee', 'dart', 'ts')
    );

    /**
     * Create a new asset instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Basset\Factory\FilterFactory  $filterFactory
     * @param  string  $absolutePath
     * @param  string  $relativePath
     * @return void
     */
    public function __construct(Filesystem $files, FilterFactory $filterFactory, $absolutePath, $relativePath)
    {
        $this->files = $files;
        $this->filterFactory = $filterFactory;
        $this->absolutePath = $absolutePath;
        $this->relativePath = $relativePath;
        $this->filters = new \Illuminate\Support\Collection;
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
     * Get the build path to the asset.
     * 
     * @return string
     */
    public function getBuildPath()
    {
        $path = pathinfo($this->relativePath);

        $fingerprint = md5($this->filters->map(function($f) { return $f->getFilter(); })->toJson().$this->getLastModified());

        return "{$path['dirname']}/{$path['filename']}-{$fingerprint}.{$this->getBuildExtension()}";
    }

    /**
     * Get the build extension of the asset.
     *
     * @return string
     */
    public function getBuildExtension()
    {
        return $this->isJavascript() ? 'js' : 'css';
    }

    /**
     * Get the last modified time of the asset.
     * 
     * @return int
     */
    public function getLastModified()
    {
        if ($this->lastModified)
        {
            return $this->lastModified;
        }

        return $this->lastModified = $this->isRemote() ? null : $this->files->lastModified($this->absolutePath);
    }

    /**
     * Determine if asset is a javascript.
     *
     * @return bool
     */
    public function isJavascript()
    {
        return $this->getGroup() == 'javascripts';
    }

    /**
     * Determine if asset is a stylesheet.
     *
     * @return bool
     */
    public function isStylesheet()
    {
        return $this->getGroup() == 'stylesheets';
    }

    /**
     * Determine if asset is remotely hosted.
     *
     * @return bool
     */
    public function isRemote()
    {
        return starts_with($this->absolutePath, '//') or (bool) filter_var($this->absolutePath, FILTER_VALIDATE_URL);
    }

    /**
     * Alias for \Basset\Asset::setOrder(1)
     *
     * @return Basset\Asset
     */
    public function first()
    {
        return $this->setOrder(1);
    }

    /**
     * Alias for \Basset\Asset::setOrder(2)
     *
     * @return \Basset\Asset
     */
    public function second()
    {
        return $this->setOrder(2);
    }

    /**
     * Alias for \Basset\Asset::setOrder(3)
     *
     * @return \Basset\Asset
     */
    public function third()
    {
        return $this->setOrder(3);
    }

    /**
     * Alias for \Basset\Asset::setOrder()
     *
     * @param  int  $order
     * @return \Basset\Asset
     */
    public function order($order)
    {
        return $this->setOrder($order);
    }

    /**
     * Set the order of the outputted asset.
     *
     * @param  int  $order
     * @return \Basset\Asset
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
     * Alias for \Basset\Asset::setExcluded(true)
     *
     * @return \Basset\Asset
     */
    public function exclude()
    {
        return $this->setExcluded(true);
    }

    /**
     * Sets the asset to be excluded.
     *
     * @param  bool  $excluded
     * @return \Basset\Asset
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
     * @return \Basset\Asset
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
     * Set the assets group.
     * 
     * @param  string  $group
     * @return \Basset\Asset
     */
    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get the assets group.
     *
     * @return string
     */
    public function getGroup()
    {
        if ($this->group)
        {
            return $this->group;
        }

        return $this->group = $this->detectGroupFromExtension() ?: $this->detectGroupFromContentType();
    }

    /**
     * Detect the group from the content type using cURL.
     * 
     * @return null|string
     */
    protected function detectGroupFromContentType()
    {
        if (extension_loaded('curl'))
        {
            $handler = curl_init($this->absolutePath);

            curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handler, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($handler, CURLOPT_HEADER, true);
            curl_setopt($handler, CURLOPT_NOBODY, true);
            curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, false);

            curl_exec($handler);

            if ( ! curl_errno($handler))
            {
                $contentType = curl_getinfo($handler, CURLINFO_CONTENT_TYPE);

                return starts_with($contentType, 'text/css') ? 'stylesheets' : 'javascripts';
            }
        }
    }

    /**
     * Detect group from the assets extension.
     * 
     * @return string
     */
    protected function detectGroupFromExtension()
    {
        $extension = pathinfo($this->absolutePath, PATHINFO_EXTENSION);

        foreach (array('stylesheets', 'javascripts') as $group)
        {
            if (in_array($extension, $this->allowedExtensions[$group]))
            {
                return $group;
            }
        }
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
        $filters = $this->prepareFilters();

        $asset = new StringAsset($this->getContent(), $filters->all(), dirname($this->absolutePath), basename($this->absolutePath));

        return $asset->dump();
    }

    /**
     * Prepare filters by filtering out those that do not apply to this asset.
     * 
     * @return \Illuminate\Support\Collection
     */
    public function prepareFilters()
    {
        return $this->filters->map(function($filter) { return $filter->getInstance(); })->filter(function($filter)
        {
            return $filter instanceof FilterInterface;
        });
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