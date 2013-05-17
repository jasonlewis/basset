<?php namespace Basset\Factory;

use Basset\Filter\Filter;
use Illuminate\Log\Writer;
use Illuminate\Config\Repository;
use Basset\Filter\FilterableInterface;

class FilterFactory implements FactoryInterface {

    /**
     * Illuminate log writer instance.
     * 
     * @var \Illuminate\Log\Writer
     */
    protected $log;

    /**
     * Array of filter aliases.
     * 
     * @var array
     */
    protected $aliases = array();

    /**
     * Array of node paths.
     * 
     * @var array
     */
    protected $nodePaths = array();

    /**
     * Application working environment.
     * 
     * @var string
     */
    protected $applicationEnvironment;

    /**
     * Create a new filter factory instance.
     * 
     * @param  \Illuminate\Log\Writer  $log
     * @param  array  $aliases
     * @param  array  $nodePaths
     * @param  string  $applicationEnvironment
     * @return void
     */
    public function __construct(Writer $log, array $aliases, array $nodePaths, $applicationEnvironment)
    {
        $this->log = $log;
        $this->aliases = $aliases;
        $this->nodePaths = $nodePaths;
        $this->applicationEnvironment = $applicationEnvironment;
    }

    /**
     * Make a new filter instance.
     *
     * @param  string|\Basset\Filter\Filter  $filter
     * @return \Basset\Filter\Filter
     */
    public function make($filter)
    {
        if ($filter instanceof Filter)
        {
            return $filter;
        }
        
        $filter = isset($this->aliases[$filter]) ? $this->aliases[$filter] : $filter;

        if (is_array($filter))
        {
            list($filter, $callback) = array(current($filter), next($filter));
        }

        // If the filter was aliased and the value of the array was a callable closure then
        // we'll return and fire the callback on the filter instance so that any arguments
        // can be set for the filters constructor.
        $filter = new Filter($this->log, $filter, $this->nodePaths, $this->applicationEnvironment);

        if (isset($callback) and is_callable($callback))
        {
            call_user_func($callback, $filter);
        }

        return $filter;
    }

}