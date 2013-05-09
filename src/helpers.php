<?php

if ( ! function_exists('basset_stylesheet'))
{
    /**
     * Ouput the stylesheets for several collections.
     * 
     * @return string
     */
    function basset_stylesheet()
    {
        $collections = array_map(function($collection) { return "{$collection}.css"; }, array_flatten(func_get_args()));

        return basset_collections($collections);
    }
}

if ( ! function_exists('basset_javascript'))
{
    /**
     * Ouput the javascripts for several collections.
     * 
     * @return string
     */
    function basset_javascript()
    {
        $collections = array_map(function($collection) { return "{$collection}.js"; }, array_flatten(func_get_args()));

        return basset_collections($collections);
    }
}

if ( ! function_exists('basset_collections'))
{
    /**
     * Output a given group for an array of collections.
     * 
     * @param  array  $collections
     * @return string
     */
    function basset_collections(array $collections)
    {
        $responses = array();

        foreach ($collections as $collection)
        {
            $responses[] = app('basset.output')->collection($collection);
        }

        return array_to_newlines($responses);
    }
}

if ( ! function_exists('array_to_newlines'))
{
    /**
     * Convert an array to a newline separated string.
     * 
     * @param  array  $array
     * @return string
     */
    function array_to_newlines(array $array)
    {
        return implode(PHP_EOL, $array);
    }
}