<?php namespace Basset\Factory;

use Basset\Asset;
use Illuminate\Filesystem\Filesystem;

class AssetFactory implements FactoryInterface {

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
     * @param  Basset\Factory\Manager  $factory
     * @param  string  $publicPath
     * @param  string  $appEnvironment
     * @return void
     */
    public function __construct(Filesystem $files, Manager $factory, $publicPath, $appEnvironment)
    {
        $this->files = $files;
        $this->factory = $factory;
        $this->publicPath = $publicPath;
        $this->appEnvironment = $appEnvironment;
    }

    /**
     * Make a new asset instance.
     *
     * @param  string  $path
     * @return Basset\Asset
     */
    public function make($path)
    {
        $absolutePath = $this->buildAbsolutePath($path);

        $relativePath = $this->buildRelativePath($absolutePath);

        return new Asset($this->files, $this->factory, $absolutePath, $relativePath, $this->appEnvironment);
    }

    /**
     * Build the absolute path to an asset.
     *
     * @param  string  $path
     * @return string
     */
    public function buildAbsolutePath($path)
    {
        if (is_null($path))
        {
            return $path;
        }

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
        if (is_null($path))
        {
            return $path;
        }

        $relativePath = trim(str_replace(array(realpath($this->publicPath), '\\'), array('', '/'), $path), '/');

        // If we're not dealing with a remote asset and the relative and absolute paths are the
        // same then it's likely the asset is outside the public path.
        if ( ! filter_var($path, FILTER_VALIDATE_URL) and trim(str_replace('\\', '/', $path), '/') == $relativePath)
        {
            list($directoryName, $fileName) = array(pathinfo($path, PATHINFO_DIRNAME), pathinfo($path, PATHINFO_BASENAME));
            
            $relativePath = md5($directoryName).'/'.$fileName;
        }

        return $relativePath;
    }

}