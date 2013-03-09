<?php

function basset_stylesheet($name)
{
    return app('basset.output')->stylesheets($name);
}

function basset_scripts($name)
{
    return app('basset.output')->javascripts($name);
}

function array_to_newlines(array $array)
{
    return implode(PHP_EOL, $array);
}