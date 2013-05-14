<?php namespace Basset\Factory;

use Basset\Asset;
use Illuminate\Filesystem\Filesystem;

class AssetFactory implements FactoryInterface {

    /**
     * Illuminate filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Basset filter factory instance.
     *
     * @var \Basset\Factory\FilterFactory
     */
    protected $filter;

    /**
     * Path to the public directory.
     *
     * @var string
     */
    protected $publicPath;

    /**
     * Number of assets produced by the factory.
     * 
     * @var int
     */
    protected $assetsProduced = 0;

    /**
     * Create a new asset factory instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Basset\Factory\FilterFactory  $filter
     * @param  string  $publicPath
     * @return void
     */
    public function __construct(Filesystem $files, FilterFactory $filter, $publicPath)
    {
        $this->files = $files;
        $this->filter = $filter;
        $this->publicPath = $publicPath;
    }

    /**
     * Make a new asset instance.
     *
     * @param  string  $path
     * @return \Basset\Asset
     */
    public function make($path)
    {
        $absolutePath = $this->buildAbsolutePath($path);

        $relativePath = $this->buildRelativePath($absolutePath);

        $asset = new Asset($this->files, $this->filter, $absolutePath, $relativePath);

        return $asset->setOrder($this->nextAssetOrder());
    }

    /**
     * Build the absolute path to an asset.
     *
     * @param  string  $path
     * @return string
     */
    public function buildAbsolutePath($path)
    {
        if (is_null($path)) return $path;

        return realpath($path) ?: $path;
    }

    /**
     * Build the relative path to an asset.
     *
     * @param  string  $path
     * @return string
     */
    public function buildRelativePath($path)
    {
        if (is_null($path)) return $path;

        $relativePath = str_replace(array(realpath($this->publicPath), '\\'), array('', '/'), $path);

        // If the asset is not a remote asset then we'll trim the relative path even further to remove
        // any unnecessary leading or trailing slashes. This will leave us with a nice relative path.
        if ( ! starts_with($path, '//') and ! (bool) filter_var($path, FILTER_VALIDATE_URL))
        {
            $relativePath = trim($relativePath, '/');

            // If the given path is the same as the built relative path then the asset appears to be
            // outside of the public directory. If this is the case then we'll use an MD5 hash of
            // the assets path as the relative path to the asset.
            if (trim(str_replace('\\', '/', $path), '/') == trim($relativePath, '/'))
            {
                $path = pathinfo($path);

                $relativePath = md5($path['dirname']).'/'.$path['basename'];
            }
        }

        return $relativePath;
    }

    /**
     * Get the next asset order.
     * 
     * @return int
     */
    protected function nextAssetOrder()
    {
        return ++$this->assetsProduced;
    }

}