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
        $c = array(); $a = func_get_args();

        array_walk_recursive($a, function($v, $k) use (&$c) { is_numeric($k) ? ($c["{$v}.css"] = null) : $c["{$k}.css"] = $v; });

        return basset_collections($c);
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
        $c = array(); $a = func_get_args();

        array_walk_recursive($a, function($v, $k) use (&$c) { is_numeric($k) ? ($c["{$v}.js"] = null) : $c["{$k}.js"] = $v; });

        return basset_collections($c);
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
        foreach ($collections as $collection => $format) $responses[] = app('basset.server')->collection($collection, $format);

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