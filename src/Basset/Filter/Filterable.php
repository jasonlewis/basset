<?php namespace Basset\Filter;

use Closure;
use Illuminate\Support\Collection;

abstract class Filterable {

    /**
     * Collection of filters.
     * 
     * @var \Illuminate\Support\Collection
     */
    protected $filters;

    /**
     * Apply a filter.
     *
     * @param  string  $filter
     * @param  \Closure  $callback
     * @return \Basset\Filter\Filter
     */
    public function apply($filter, Closure $callback = null)
    {
        $filter = $this->factory['filter']->make($filter);

        $filter->setResource($this);

        if (is_callable($callback))
        {
            call_user_func($callback, $filter);
        }

        return $this->filters[$filter->getFilter()] = $filter;
    }

    /**
     * Get the applied filters.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Create a new collection instance.
     * 
     * @param  array  $items
     * @return \Illuminate\Support\Collection
     */
    public function newCollection(array $items = array())
    {
        return new Collection($items);
    }

}