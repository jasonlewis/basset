<?php namespace Basset;

use Basset\Filter\Filter;
use Illuminate\Config\Repository;
use Basset\Filter\FilterableInterface;

class FilterFactory {

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
     * @param  Closure  $callback
     * @param  Basset\Filter\FilterableInterface  $resource
     * @return Basset\Filter\Filter
     */
    public function make($filter, $callback, FilterableInterface $resource)
    {
        if ($filter instanceof Filter)
        {
            return $filter;
        }
        elseif ($this->config->has("basset::filters.{$filter}"))
        {
            $filter = $this->config->get("basset::filters.{$filter}");

            if (is_array($filter))
            {
                list($filter, $callback) = array(key($filter), current($filter));
            }
        }

        $filterInstance = new Filter($filter, $resource);

        if (is_callable($callback))
        {
            call_user_func($callback, $filterInstance);
        }

        return $filterInstance;
    }

}