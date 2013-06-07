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
     * @param  string|array  $filter
     * @param  \Closure  $callback
     * @return \Basset\Filter\Filter
     */
    public function apply($filter, Closure $callback = null)
    {
        // If the supplied filter is an array then we'll treat it as an array of filters that are
        // to be applied to the resource.
        if (is_array($filter))
        {
            return $this->applyFromArray($filter);
        }

        $filter = $this->filterFactory->make($filter)->setResource($this);

        is_callable($callback) and call_user_func($callback, $filter);

        return $this->filters[$filter->getFilter()] = $filter;
    }

    /**
     * Apply filter from an array of filters.
     * 
     * @param  array  $filters
     * @return \Basset\Filter\Filterable
     */
    public function applyFromArray($filters)
    {
        foreach ($filters as $key => $value)
        {
            $filter = $this->filterFactory->make(is_callable($value) ? $key : $value)->setResource($this);

            is_callable($value) and call_user_func($value, $filter);

            $this->filters[$filter->getFilter()] = $filter;
        }

        return $this;
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