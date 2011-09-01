<?php use Basset\Basset as Basset;
return array(

	/**
	 * This is an example route using the auto content type detection.
	 * Replace this with your own filter.
	 * 
	 * See the README for more details.
	 */
	'GET /example/template.css' => function()
	{
		return Basset::add('reset', 'reset.css')
			->add('template', 'template.css');
	}
);