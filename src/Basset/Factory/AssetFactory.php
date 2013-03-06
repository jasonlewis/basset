<?php namespace Basset\Factory;

use Basset\Asset;
use Illuminate\Filesystem\Filesystem;

class AssetFactory {

    /**
     * Illuminate filesystem instance.
     *
     * @var Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Filter factory instance.
     *
     * @var Basset\Factory\FilterFactory
     */
    protected $filter;

    /**
     * Path to the public directory.
     *
     * @var string
     */
    protected $publicPath;

    /**
     * Create a new asset factory instance.
     *
     * @param  Illuminate\Filesystem\Filesystem  $files
     * @param  Basset\Factory\FilterFactory  $filter
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
     * Make a new asset instance, resolving the absolute and relative paths.
     *
     * @param  string  $path
     * @return Basset\Asset
     */
    public function make($path)
    {
        $absolutePath = $this->buildAbsolutePath($path);

        $relativePath = $this->buildRelativePath($absolutePath);

        return new Asset($this->files, $this->filter, $absolutePath, $relativePath);
    }

    /**
     * Build the absolute path to an asset.
     *
     * @param  string  $path
     * @return string
     */
    public function buildAbsolutePath($path)
    {
        return filter_var($path, FILTER_VALIDATE_URL) ? $path : realpath($path);
    }

    /**
     * Build the relative path to an asset.
     *
     * @param  string  $path
     * @return string
     */
    public function buildRelativePath($path)
    {
        $relativePath = trim(str_replace(array(realpath($this->publicPath), '\\'), array('', '/'), $path), '/');

        // If we're not dealing with a remote asset and the relative and absolute paths are the
        // same then it's likely the asset is outside the public path.
        if ( ! filter_var($path, FILTER_VALIDATE_URL) and str_replace('\\', '/', $path) == $relativePath)
        {
            list($directoryName, $fileName) = array(pathinfo($path, PATHINFO_DIRNAME), pathinfo($path, PATHINFO_BASENAME));

            $relativePath = md5($directoryName).'/'.$fileName;
        }

        return $relativePath;
    }

}