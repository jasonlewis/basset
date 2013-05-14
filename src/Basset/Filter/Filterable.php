<?php namespace Basset\Filter;

use Closure;

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
        $filter = $this->filterFactory->make($filter);

        $filter->setResource($this);

        is_callable($callback) and call_user_func($callback, $filter);

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

}