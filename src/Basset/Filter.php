<?php namespace Basset;

use Closure;
use ReflectionClass;

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
     * @var asset\FilterableInterface
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
     * Create a new filter instance.
     *
     * @param  string  $filter
     * @return void
     */
    public function __construct($filter, FilterableInterface $resource)
    {
        $this->filter = $filter;
        $this->resource = $resource;
    }

    /**
     * Add a before filtering callback.
     *
     * @param  Closure  $callback
     * @return asset\Filter
     */
    public function beforeFiltering(Closure $callback)
    {
        $this->before[] = $callback;

        return $this;
    }

    /**
     * Set the filters instantiation arguments
     *
     * @return asset\Filter
     */
    public function setArguments()
    {
        $this->arguments = array_merge($this->arguments, func_get_args());

        return $this;
    }

    /**
     * Add an environment to apply the filter on.
     *
     * @param  string  $environment
     * @return asset\Filter
     */
    public function onEnvironment($environment)
    {
        $this->environments[] = $environment;

        return $this;
    }

    /**
     * Add an array of environments to apply the filter on.
     *
     * @return asset\Filter
     */
    public function onEnvironments()
    {
        $this->environments = array_merge($this->environments, func_get_args());

        return $this;
    }

    /**
     * Apply filter to only styles.
     *
     * @return asset\Filter
     */
    public function onlyStyles()
    {
        $this->groupRestriction = 'styles';

        return $this;
    }

    /**
     * Apply filter to only scripts.
     *
     * @return asset\Filter
     */
    public function onlyScripts()
    {
        $this->groupRestriction = 'scripts';

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
     * Check if the filter exists.
     *
     * @return string|bool
     */
    public function exists()
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
     * Attempt to instantiate the filter if it exists.
     *
     * @return mixed
     */
    public function instantiate()
    {
        if ($className = $this->exists())
        {
            $reflection = new ReflectionClass($className);

            // If no constructor is available on the filters class then we'll instantiate
            // the filter without passing in any arguments.
            if ( ! $reflection->getConstructor())
            {
                $reflectionInstance = $reflection->newInstance();
            }
            else
            {
                $reflectionInstance = $reflection->newInstanceArgs($this->arguments);
            }

            // Spin through each of the before filtering callbacks and fire each one. We'll
            // pass in an instance of the filter to the callback.
            foreach ($this->before as $callback)
            {
                if (is_callable($callback))
                {
                    call_user_func($callback, $reflectionInstance);
                }
            }

            return $reflectionInstance;
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