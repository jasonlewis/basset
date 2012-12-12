<?php

/*
|--------------------------------------------------------------------------
| Basset Version
|--------------------------------------------------------------------------
|
| Define Basset's version.
|
*/

define('BASSET_VERSION', '3.0.0');

/*
|--------------------------------------------------------------------------
| Register PSR-0 Autoloading
|--------------------------------------------------------------------------
|
| Basset uses PSR-0 autoloading to lazily load the required files when
| requested. Here we'll provide the namespaces and their corrosponding
| locations.
|
*/

Autoloader::namespaces(array(
	'Assetic' => __DIR__.'/vendor/assetic/src/Assetic',
	'Basset' => __DIR__.'/classes'
));

/*
|--------------------------------------------------------------------------
| Basset Facade Alias
|--------------------------------------------------------------------------
|
| Alias Basset to the Basset Facade so that we can use a terser static
| syntax to access methods. Lovely.
|
*/

Autoloader::alias('Basset\Facades\Basset', 'Basset');

/*
|--------------------------------------------------------------------------
| Register Basset with the IoC
|--------------------------------------------------------------------------
|
| Basset is registered within the IoC container so that everything is
| somewhat testable. We'll use a facade to provide a terser static
| interface.
|
*/

IoC::instance('basset', new Basset\Basset);