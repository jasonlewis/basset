<?php namespace Basset\Filter;

use Closure;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Process\ExecutableFinder;

class Filter {

    /**
     * Array of instantiation arguments.
     *
     * @var array
     */
    protected $arguments = array();

    /**
     * Array of before filtering callbacks.
     *
     * @var array
     */
    protected $before = array();

    /**
     * Filter name.
     *
     * @var string
     */
    protected $filter;

    /**
     * Resource being filtered.
     *
     * @var Basset\FilterableInterface
     */
    protected $resource;

    /**
     * Array of environments to apply filter on.
     *
     * @var array
     */
    protected $environments = array();

    /**
     * Group to restrict the filter to.
     *
     * @var string
     */
    protected $groupRestriction;

    /**
     * Array of node module paths.
     * 
     * @var array
     */
    protected $nodePaths = array();

    /**
     * Array of valid executable argument suffixes.
     * 
     * @var array
     */
    protected $validSuffixes = array('bin', 'path');

    /**
     * Indicates if the filter should be ignored when building assets.
     * 
     * @var bool
     */
    protected $ignored = false;

    /**
     * Asset filename pattern to have the asset applied.
     * 
     * @var string
     */
    protected $filenamePattern;

    /**
     * Create a new filter instance.
     *
     * @param  string  $filter
     * @param  array  $nodePaths
     * @return void
     */
    public function __construct($filter, array $nodePaths = array())
    {
        $this->filter = $filter;
        $this->nodePaths = $nodePaths;
    }

    /**
     * Find and set any missing constructor arguments.
     * 
     * @return Basset\Filter\Filter
     */
    public function findMissingConstructorArgs()
    {
        try
        {
            $class = new ReflectionClass($this->getClassName());
        }
        catch (ReflectionException $e)
        {
            return $this;
        }

        if ($constructor = $class->getConstructor())
        {
            $finder = $this->getExecutableFinder();

            // Spin through all of the constructor parameters and for those that we can find the executable
            // path for we'll attempt to locate the executable. If we can't find the path then its more
            // then we'll ignore this filter as it will fail during the build.
            foreach ($constructor->getParameters() as $key => $parameter)
            {
                if ($this->hasArgumentAtPosition($key))
                {
                    continue;
                }

                $snakeParameter = $this->normalizeConstructorParameter($parameter->name);

                list($name, $suffix) = explode('_', $snakeParameter);

                // If the suffix is in the array of valid suffixes we can attempt to locate the parameter
                // first as an environment variable and secondly by recursively searching through our
                // paths defined in PATH. If we can't find it then ignore the filter.
                if (in_array($suffix, $this->validSuffixes))
                {
                    $path = $this->getEnvironmentVariable($snakeParameter) ?: $finder->find($name);

                    if ($path)
                    {
                        $this->setArgument($path, $key);
                    }
                    else
                    {
                        $this->ignored = true;
                    }
                }
                elseif(str_is('nodePaths', $parameter->name))
                {
                    $this->setArgument($this->nodePaths, $key);
                }
            }
        }

        return $this;
    }

    /**
     * Get an environment variable.
     * 
     * @param  string  $key
     * @return string|bool
     */
    public function getEnvironmentVariable($key)
    {
        return getenv(strtoupper($key));
    }

    /**
     * Convert a constructor parameter to snake case and all lowercase.
     * 
     * @param  string  $name
     * @return string
     */
    protected function normalizeConstructorParameter($name)
    {
        return strtolower(snake_case($name));
    }

    /**
     * Get an executable finder instance.
     * 
     * @return Symfony\Component\Process\ExecutableFinder
     */
    public function getExecutableFinder()
    {
        return new ExecutableFinder;
    }

    /**
     * Add a before filtering callback.
     *
     * @param  Closure  $callback
     * @return Basset\Filter\Filter
     */
    public function beforeFiltering(Closure $callback)
    {
        $this->before[] = $callback;

        return $this;
    }

    /**
     * Determine if the filter has an instantiation argument at a given position.
     * 
     * @param  int  $position
     * @return bool
     */
    public function hasArgumentAtPosition($position)
    {
        return isset($this->arguments[$position]);
    }

    /**
     * Set a single instantiation argument.
     * 
     * @param  string  $argument
     * @param  int  $position
     * @return Basset\Filter\Filter
     */
    public function setArgument($argument, $position = null)
    {
        array_splice($this->arguments, $position ?: count($this->arguments), 0, array($argument));

        return $this;
    }

    /**
     * Set the filters instantiation arguments
     *
     * @return Basset\Filter\Filter
     */
    public function setArguments()
    {
        $this->arguments = array_merge($this->arguments, func_get_args());

        return $this;
    }

    /**
     * Set file pattern that the filter will be applied to.
     * 
     * @param  string  $pattern
     * @return Basset\Filter\Filter
     */
    public function to($pattern)
    {
        $this->filenamePattern = $pattern;

        return $this;
    }

    /**
     * Get the file pattern.
     * 
     * @return string
     */
    public function getFilenamePattern()
    {
        return $this->filenamePattern;
    }

    /**
     * Determine if filter has a file pattern.
     * 
     * @return string
     */
    public function hasFilenamePattern()
    {
        return ! is_null($this->filenamePattern);
    }

    /**
     * Add an environment to apply the filter on.
     *
     * @param  string  $environment
     * @return Basset\Filter\Filter
     */
    public function onEnvironment($environment)
    {
        $this->environments[] = $environment;

        return $this;
    }

    /**
     * Add an array of environments to apply the filter on.
     *
     * @return Basset\Filter\Filter
     */
    public function onEnvironments()
    {
        $this->environments = array_merge($this->environments, func_get_args());

        return $this;
    }

    /**
     * Apply filter to only stylesheets.
     *
     * @return Basset\Filter\Filter
     */
    public function onlyStylesheets()
    {
        $this->groupRestriction = 'stylesheets';

        return $this;
    }

    /**
     * Apply filter to only javascripts.
     *
     * @return Basset\Filter\Filter
     */
    public function onlyJavascripts()
    {
        $this->groupRestriction = 'javascripts';

        return $this;
    }

    /**
     * Set the resource on the filter.
     * 
     * @param  Basset\Filter\FilterableInterface  $resource
     * @return Basset\Filter\Filter
     */
    public function setResource(FilterableInterface $resource)
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Get the parent resource.
     *
     * @return mixed
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Get the filter name.
     *
     * @return string
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Get the filters group restriction.
     *
     * @return string
     */
    public function getGroupRestriction()
    {
        return $this->groupRestriction;
    }

    /**
     * Get the array of environments.
     *
     * @return array
     */
    public function getEnvironments()
    {
        return $this->environments;
    }

    /**
     * Get the filters instantiation arguments.
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Determine if filter is ignored.
     * 
     * @return bool
     */
    public function isIgnored()
    {
        return $this->ignored;
    }

    /**
     * Fire a callback passing in the filter instance as a parameter.
     * 
     * @param  Closure  $callback
     * @return Basset\Filter\Filter
     */
    public function fireCallback(Closure $callback = null)
    {
        if (is_callable($callback))
        {
            call_user_func($callback, $this);
        }

        return $this;
    }

    /**
     * Get the class name for the filter if it exists.
     *
     * @return string|bool
     */
    public function getClassName()
    {
        if (class_exists("Assetic\\Filter\\{$this->filter}"))
        {
            return "Assetic\\Filter\\{$this->filter}";
        }
        elseif (class_exists("Basset\\Filter\\{$this->filter}"))
        {
            return "Basset\\Filter\\{$this->filter}";
        }

        return false;
    }

    /**
     * Attempt to instantiate the filter if it exists and has not been ignored.
     *
     * @return mixed
     */
    public function getInstance()
    {
        $class = $this->getClassName();

        if ($class and ! $this->ignored)
        {
            $reflection = new ReflectionClass($class);

            // If no constructor is available on the filters class then we'll instantiate
            // the filter without passing in any arguments.
            if ( ! $reflection->getConstructor())
            {
                $instance = $reflection->newInstance();
            }
            else
            {
                $instance = $reflection->newInstanceArgs($this->arguments);
            }

            // Spin through each of the before filtering callbacks and fire each one. We'll
            // pass in an instance of the filter to the callback.
            foreach ($this->before as $callback)
            {
                if (is_callable($callback))
                {
                    call_user_func($callback, $instance);
                }
            }

            return $instance;
        }
    }

    /**
     * Dynamically chain uncallable methods to the parent resource.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array(array($this->resource, $method), $parameters);
    }

}