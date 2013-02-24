<?php

function basset_stylesheet($name)
{
    return app('basset.output')->styles($name);
}

function basset_scripts($name)
{
    return app('basset.output')->scripts($name);
}

function array_to_newlines(array $array)
{
    return implode(PHP_EOL, $array);
}