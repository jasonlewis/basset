<?php

if ( ! function_exists('basset_stylesheet'))
{
    /**
     * Ouput the stylesheets for a given collection.
     * 
     * @param  string  $collection
     * @return string
     */
    function basset_stylesheet($name)
    {
        return app('basset.output')->stylesheets($name);
    }
}

if ( ! function_exists('basset_javascript'))
{
    /**
     * Ouput the javascripts for a given collection.
     * 
     * @param  string  $collection
     * @return string
     */
    function basset_javascript($collection)
    {
        return app('basset.output')->javascripts($collection);
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