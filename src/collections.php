<?php

Basset::collection('website', function($collection)
{
	$collection->directory('css', function($collection)
	{
		$collection->requireTree()->only('example.css')->apply('YuiCss');
	});
});