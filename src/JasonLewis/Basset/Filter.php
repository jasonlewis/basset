<?php namespace JasonLewis\Basset;

use Closure;

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
	 * Fully qualified filter name.
	 * 
	 * @var string
	 */
	protected $filter;

	/**
	 * Array of environments to apply filter on.
	 * 
	 * @var array
	 */
	protected $environments = array();

	/**
	 * Group to apply filter to.
	 * 
	 * @var string
	 */
	protected $group;

	/**
	 * Create a new filter instance.
	 * 
	 * @param  string  $filter
	 * @return void
	 */
	public function __construct($filter)
	{
		$this->filter = $filter;
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
		$this->group = 'styles';

		return $this;
	}

	/**
	 * Apply filter to only scripts.
	 * 
	 * @return JasonLewis\Basset\Filter
	 */
	public function onlyScripts()
	{
		$this->group = 'scripts';

		return $this;
	}

}