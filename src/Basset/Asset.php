<?php namespace Basset;

use Illuminate\Log\Writer;
use Basset\Filter\Filterable;
use InvalidArgumentException;
use Assetic\Asset\StringAsset;
use Basset\Factory\FilterFactory;
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
     * Illuminate log writer instance.
     * 
     * @var \Illuminate\Log\Writer
     */
    protected $log;

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
     * Indicates if the asset is to be served raw.
     *
     * @var bool
     */
    protected $raw = false;

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
     * @param  \Illuminate\Log\Writer  $log
     * @param  string  $absolutePath
     * @param  string  $relativePath
     * @return void
     */
    public function __construct(Filesystem $files, FilterFactory $filterFactory, Writer $log, $absolutePath, $relativePath)
    {
        $this->files = $files;
        $this->filterFactory = $filterFactory;
        $this->log = $log;
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
            $this->log->warning('Attempting to determine asset group using cURL. This may have a considerable effect on application speed.');

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
     * A raw asset is just excluded from the build process.
     * 
     * @return \Basset\Asset
     */
    public function raw()
    {
        $this->raw = true;

        return $this;
    }

    /**
     * Determines if the asset is to be served raw.
     * 
     * @return bool
     */
    public function serveRaw()
    {
        return $this->raw;
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
     * @param  bool  $production
     * @return string
     */
    public function build($production = false)
    {
        $filters = $this->prepareFilters($production);

        $asset = new StringAsset($this->getContent(), $filters->all(), dirname($this->absolutePath), basename($this->absolutePath));

        return $asset->dump();
    }

    /**
     * Prepare the filters applied to the asset.
     * 
     * @param  bool  $production
     * @return \Illuminate\Support\Collection
     */
    public function prepareFilters($production = false)
    {
        $filters = $this->filters->map(function($filter) use ($production)
        {
            $filter->setProduction($production);

            return $filter->getInstance();
        });

        return $filters->filter(function($filter) { return $filter instanceof FilterInterface; });
    }

}