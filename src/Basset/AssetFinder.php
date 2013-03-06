<?php namespace Basset;

use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Basset\Exception\AssetNotFoundException;

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
     * Find an asset.
     *
     * @param  string  $name
     * @return string
     */
    public function find($name)
    {
        // Determine if the asset has been given an alias. We'll use the alias as the name of
        // the asset.
        if ($this->config->has("basset::assets.{$name}"))
        {
            $name = $this->config->get("basset::assets.{$name}");
        }

        $possibilities = array('RemotelyHosted', 'PackagedAsset', 'AbsolutePath', 'WorkingDirectory', 'PublicPath', 'NamedDirectories');

        foreach ($possibilities as $posibility)
        {
            $path = $this->{'findBy'.$posibility}($name);

            if ( ! is_null($path))
            {
                return $path;
            }
        }

        throw new AssetNotFoundException("Asset [{$name}] could not be found.");
    }

    /**
     * Find an asset by being remotely hosted.
     *
     * @param  string  $name
     * @return null|string
     */
    public function findByRemotelyHosted($name)
    {
        if (filter_var($name, FILTER_VALIDATE_URL))
        {
            return $name;
        }
    }

    /**
     * Find an asset by looking for a prefixed package name.
     *
     * @param  string  $name
     * @return null|string
     */
    public function findByPackagedAsset($name)
    {
        if (str_contains($name, '::'))
        {

        }
    }

    /**
     * Find an asset by its absolute path.
     *
     * @param  string  $name
     * @return null|string
     */
    public function findByAbsolutePath($name)
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
    public function findByWorkingDirectory($name)
    {
        // Determine if the asset exists within the current working directory.
        if ( ! is_null($this->workingDirectory) and $this->files->exists($this->workingDirectory.'/'.$name))
        {
            return $this->workingDirectory.'/'.$name;
        }
    }

    /**
     * Find an asset by searching in the public path.
     *
     * @param  string  $name
     * @return null|string
     */
    public function findByPublicPath($name)
    {
        $path = $this->prefixPublicPath($name);

        if ($this->files->exists($path))
        {
            return $path;
        }
    }

    /**
     * Find an asset by searching through the named directories.
     *
     * @param  string  $name
     * @return null|string
     */
    public function findByNamedDirectories($name)
    {
        foreach ($this->config->get('basset::directories') as $directoryName => $directoryPath)
        {
            $directory = $this->parseDirectoryPath($directoryPath);

            if ( ! $directory instanceof Directory)
            {
                continue;
            }

            // Recursively spin through each directory. We're simply looking for a file that has
            // the same ending as the name of the file. Once we've found it we'll bail out of
            // both loops.
            foreach ($directory->recursivelyIterateDirectory($directory->getPath()) as $file)
            {
                $filePath = $file->getRealPath();

                if (ends_with(str_replace('\\', '/', $filePath), $name))
                {
                    return $filePath;
                }
            }
        }
    }

    /**
     * Set the working directory path.
     *
     * @param  string  $path
     * @return void
     */
    public function setWorkingDirectory($path)
    {
        $this->workingDirectory = $path;
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
     * Parse a directory path.
     *
     * @param  string  $directory
     * @return string
     */
    public function parseDirectoryPath($path)
    {
        if (starts_with($path, 'name: '))
        {
            $name = substr($path, 6);

            if ($this->config->has("basset::directories.{$name}"))
            {
                $path = $this->config->get("basset::directories.{$name}");
            }
        }

        if (starts_with($path, 'path: '))
        {
            $path = substr($path, 6);
        }
        else
        {
            $path = $this->prefixPublicPath($path);
        }

        return $path;
    }

    /**
     * Prefix the public path to a given path.
     *
     * @param  string  $path
     * @return string
     */
    public function prefixPublicPath($path)
    {
        return $this->publicPath.'/'.$path;
    }

}