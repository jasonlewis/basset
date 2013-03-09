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
        
        $filter = $this->config->get("basset::filters.{$filter}", $filter);

        if (is_array($filter))
        {
            list($filter, $callback) = array(key($filter), current($filter));
        }

        return new Filter($filter);
    }

}