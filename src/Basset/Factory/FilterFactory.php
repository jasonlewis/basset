<?php namespace Basset\Factory;

use Basset\Filter\Filter;
use Illuminate\Config\Repository;
use Basset\Filter\FilterableInterface;

class FilterFactory implements FactoryInterface {

    /**
     * Illuminate config repository.
     *
     * @var Illuminate\Config\Repository
     */
    protected $config;

    /**
     * Create a new filter factory instance.
     *
     * @param  Illuminate\Config\Repository  $config
     * @return void
     */
    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    /**
     * Make a new filter instance.
     *
     * @param  Basset\Filter\Filter|string  $filter
     * @return Basset\Filter\Filter
     */
    public function make($filter)
    {
        if ($filter instanceof Filter)
        {
            return $filter;
        }
        
        $filter = $this->config->get("basset::aliases.filters.{$filter}", $filter);

        if (is_array($filter))
        {
            list($filter, $callback) = array(key($filter), current($filter));
        }

        // If the filter was aliased and the value of the array was a callable closure then
        // we'll return and fire the callback on the filter instance so that any arguments
        // can be set for the filters constructor.
        $filter = new Filter($filter, $this->config->get('basset::node_paths'));

        isset($callback) and $filter->runCallback($callback);

        return $filter;
    }

}