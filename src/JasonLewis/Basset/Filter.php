<?php namespace JasonLewis\Basset;

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
	 * Array of events to fire before filtering.
	 * 
	 * @var array
	 */
	protected $before = array();

	/**
	 * Array of events to fire after filtering.
	 * 
	 * @var array
	 */
	protected $after = array();

	/**
	 * Filter name.
	 * 
	 * @var string
	 */
	protected $filter;

	/**
	 * Resource being filtered.
	 * 
	 * @var JasonLewis\Basset\FilterableInterface
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
	 * Set the filters instantiation arguments
	 * 
	 * @param  mixed  $argument
	 * @return JasonLewis\Basset\Filter
	 */
	public function setArguments($arguments)
	{
		if ( ! is_array($arguments))
		{
			$arguments = array($arguments);
		}

		$this->arguments[] = $arguments;

		return $this;
	}

	/**
	 * Add a before filtering event.
	 * 
	 * @param  Closure  $callback
	 * @return JasonLewis\Basset\Filter
	 */
	public function beforeFiltering(Closure $callback)
	{
		$this->before[] = $callback;

		return $this;
	}

	/**
	 * Add an after filtering event.
	 * 
	 * @param  Closure  $callback
	 * @return JasonLewis\Basset\Filter
	 */
	public function afterFiltering(Closure $callback)
	{
		$this->after[] = $callback;
	}

	/**
	 * Add an environment to apply the filter on.
	 * 
	 * @param  string  $environment
	 * @return JasonLewis\Basset\Filter
	 */
	public function onEnvironment($environment)
	{
		$this->environments[] = $environment;

		return $this;
	}

	/**
	 * Add an array of environments to apply the filter on.
	 * 
	 * @param  array  $environments
	 * @return JasonLewis\Basset\Filter
	 */
	public function onEnvironments(array $environments)
	{
		$this->environments = array_merge($this->environments, $environments);

		return $this;
	}

	/**
	 * Apply filter to only styles.
	 * 
	 * @return JasonLewis\Basset\Filter
	 */
	public function onlyStyles()
	{
		$this->groupRestriction = 'styles';

		return $this;
	}

	/**
	 * Apply filter to only scripts.
	 * 
	 * @return JasonLewis\Basset\Filter
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
	 * Attempt to instantiate the filter if it exists.
	 * 
	 * @return mixed
	 */
	public function instantiate()
	{
		if (class_exists($filter = "Assetic\\Filter\\{$this->filter}") or class_exists($filter = "JasonLewis\\Basset\\Filter\\{$this->filter}"))
		{
			$reflection = new ReflectionClass($filter);

			return $reflection->newInstanceArgs($this->arguments);
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