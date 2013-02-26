<?php namespace Basset;

use Illuminate\Filesystem\Filesystem;

class AssetFactory {

    /**
     * Illuminate filesystem instance.
     *
     * @var Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Path to the public directory.
     *
     * @var string
     */
    protected $publicPath;

    /**
     * Application working environment.
     *
     * @var string
     */
    protected $appEnvironment;

    /**
     * Create a new asset factory instance.
     *
     * @param  Illuminate\Filesystem\Filesystem  $files
     * @param  string  $publicPath
     * @param  string  $appEnvironment
     * @return void
     */
    public function __construct(Filesystem $files, FilterFactory $filterFactory, $publicPath, $appEnvironment)
    {
        $this->files = $files;
        $this->filterFactory = $filterFactory;
        $this->publicPath = $publicPath;
        $this->appEnvironment = $appEnvironment;
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

        return new Asset($this->files, $this->filterFactory, $absolutePath, $relativePath, $this->appEnvironment);
    }

    /**
     * Determine if an asset exists relative from the public directory.
     *
     * @param  string  $path
     * @return bool
     */
    public function find($path)
    {
        return $this->files->exists($this->publicPath.'/'.$path);
    }

    /**
     * Get the absolute path to an asset relative to the public directory.
     *
     * @param  string  $path
     * @return string
     */
    public function path($path)
    {
        return $this->publicPath.'/'.$path;
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