<?php namespace Basset;

use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Basset\Exception\AssetNotFoundException;
use Basset\Exception\DirectoryNotFoundException;

class AssetFinder {

    /**
     * Illuminate filesystem instance.
     *
     * @var Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Illuminate config repository instance.
     *
     * @var Illuminate\Config\Repository
     */
    protected $config;

    /**
     * Path to the working directory.
     *
     * @var string
     */
    protected $workingDirectory;

    /**
     * Array of package namespace hints.
     * 
     * @var array
     */
    protected $hints = array();

    /**
     * Create a new asset finder instance.
     *
     * @param  Illuminate\Filesystem\Filesystem  $files
     * @param  Illuminate\Config\Repository  $config
     * @param  string  $publicPath
     * @return void
     */
    public function __construct(Filesystem $files, Repository $config, $publicPath)
    {
        $this->files = $files;
        $this->config = $config;
        $this->publicPath = $publicPath;
    }

    /**
     * Find and return an assets path.
     *
     * @param  string  $name
     * @return string
     */
    public function find($name)
    {
        $name = $this->config->get("basset::aliases.assets.{$name}", $name);

        // Spin through an array of methods ordered by the priority of how an asset should be found.
        // Once we find a non-null path we'll return that path breaking from the loop.
        foreach (array('RemotelyHosted', 'PackagedAsset', 'AbsolutePath', 'WorkingDirectory', 'PublicPath') as $method)
        {
            if ($path = $this->{"find{$method}"}($name))
            {
                return $path;
            }
        }

        throw new AssetNotFoundException("Asset [{$name}] could not be found.");
    }

    /**
     * Find a remotely hosted asset.
     *
     * @param  string  $name
     * @return null|string
     */
    public function findRemotelyHosted($name)
    {
        if (filter_var($name, FILTER_VALIDATE_URL))
        {
            return $name;
        }
    }

    /**
     * Find an asset by looking for a prefixed package namespace.
     *
     * @param  string  $name
     * @return null|string
     */
    public function findPackagedAsset($name)
    {
        if (str_contains($name, '::'))
        {
            list($namespace, $name) = explode('::', $name);

            if ( ! isset($this->hints[$namespace]))
            {
                return;
            }

            $path = $this->prefixPublicPath("packages/{$this->hints[$namespace]}/{$name}");

            if ($this->files->exists($path))
            {
                return $path;
            }
        }
    }

    /**
     * Find an asset by its absolute path.
     *
     * @param  string  $name
     * @return null|string
     */
    public function findAbsolutePath($name)
    {
        // If the name of the asset is prefixed with 'path: ' then the absolute path to the asset
        // is being provided. This is best avoided as assets should always be within the public
        // directory.
        if (starts_with($name, 'path: '))
        {
            return substr($name, 6);
        }
    }

    /**
     * Find an asset by searching in the current working directory.
     *
     * @param  string  $name
     * @return null|string
     */
    public function findWorkingDirectory($name)
    {
        $path = $this->workingDirectory.'/'.$name;

        if ( ! is_null($this->workingDirectory) and $this->files->exists($path))
        {
            return $path;
        }
    }

    /**
     * Find an asset by searching in the public path.
     *
     * @param  string  $name
     * @return null|string
     */
    public function findPublicPath($name)
    {
        $path = $this->prefixPublicPath($name);

        if ($this->files->exists($path))
        {
            return $path;
        }
    }

    /**
     * Set the working directory path.
     *
     * @param  string  $path
     * @return string
     */
    public function setWorkingDirectory($path)
    {
        $path = $this->prefixPublicPath($path);

        if ($this->files->exists($path))
        {
            return $this->workingDirectory = $path;
        }

        throw new DirectoryNotFoundException("Directory [{$path}] could not be found.");
    }

    /**
     * Reset the working directory path.
     *
     * @return void
     */
    public function resetWorkingDirectory()
    {
        $this->workingDirectory = null;
    }

    /**
     * Get the working directory path.
     *
     * @return string
     */
    public function getWorkingDirectory()
    {
        return $this->workingDirectory;
    }

    /**
     * Add a package namespace.
     * 
     * @param  string  $package
     * @param  string  $namespace
     * @return void
     */
    public function addNamespace($package, $namespace)
    {
        $this->hints[$namespace] = $package;
    }

    /**
     * Prefix the public path to a path.
     *
     * @param  string  $path
     * @return string
     */
    protected function prefixPublicPath($path)
    {
        return $this->publicPath.'/'.$path;
    }

    /**
     * Get the illuminate filesystem instance.
     * 
     * @return Illuminate\Filesystem\Filesystem
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Get the illuminate config repository instance.
     * 
     * @return Illuminate\Config\Repository
     */
    public function getConfig()
    {
        return $this->config;
    }

}