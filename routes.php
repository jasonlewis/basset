<?php
Basset::css('website', function($basset)
{
	$basset->add('normalize', 'normalize.css')
		   ->add('website', 'main.css')
		   ->add('forms', 'forms.css')
		   ->add('tooltip', 'tooltip.css');
});

Basset::js('website', function($basset)
{
	$basset->add('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js')
		    ->add('underscore', 'vendor/underscore.js')
		    ->add('json', 'vendor/json.js')
		    ->add('placeholder', 'vendor/placeholder.js')
		    ->add('tooltip', 'vendor/tooltip.js')
		    ->add('follow', 'follow.js')
		    ->add('updates', 'updates.js')
		    ->add('socket.io', URL::base() . ':' . Config::get('site.node.port') . '/socket.io/socket.io.js')
		    ->add('notifications', 'notifications.js')
		    ->add('website', 'main.js');
});