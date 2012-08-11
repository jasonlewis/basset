<?php

class BassetTest extends PHPUnit_Framework_TestCase {

	public function setUp()
	{
		Bundle::start('basset');

		if(!file_exists(__DIR__ . '/mock'))
		{
			mkdir(__DIR__ . '/mock');
		}
	}

	public function tearDown()
	{
		if(file_exists(__DIR__ . '/mock'))
		{
			rmdir(__DIR__ . '/mock');
		}
	}

	public function testContainersCanBeCreated()
	{
		$container = new Basset\Container(null);

		$this->assertTrue($container == new Basset\Container(null));
		$this->assertInstanceOf('Basset\\Container', $container);
	}

	public function testStyleRoutesCanBeCreated()
	{
		Basset::styles('mock', function($basset){});

		$route = Bundle::option('basset', 'handles') . '/mock.css';

		$this->routesCanBeCreated($route);
	}

	public function testScriptRoutesCanBeCreated()
	{
		Basset::scripts('mock', function($basset){});

		$route = Bundle::option('basset', 'handles') . '/mock.js';

		$this->routesCanBeCreated($route);
	}

	private function routesCanBeCreated($route)
	{
		$this->assertArrayHasKey($route, Basset::$routes);
		$this->assertInstanceOf('Laravel\\Routing\\Route', Laravel\Routing\Router::route('GET', $route));
	}

	public function testInlineContainerCanBeCreated()
	{
		$container = Basset::inline('mock');

		$this->assertTrue($container === Basset::inline('mock'));
		$this->assertArrayHasKey('inline::mock', Basset::$inline);
	}

	public function testAssetsCanBeCompiled()
	{
		file_put_contents($file = __DIR__ . '/mock/mock.css', $contents = 'body { background-color: #ff0000; }');

		Basset::styles('mock', function($basset)
		{
			$basset->directory('path: ' . __DIR__ . '/mock', function($basset)
			{
				$basset->add('mock', 'mock.css');
			});
		});

		URI::$uri = Bundle::option('basset', 'handles') . '/mock.css';

		Basset::compile();

		$compiled = Basset::compiled();

		unlink($file);

		$this->assertTrue(trim($compiled) === $contents);
	}

	public function testBassetURLGenerated()
	{
		Basset::styles('mock', function($basset){});

		$route = URL::to(Bundle::option('basset', 'handles') . '/mock.css');

		$this->assertTrue(HTML::style($route) === Basset::show('mock.css'));
	}

	public function testAssetsCanBeShared()
	{
		Basset::share('mock', 'mock.css');

		$this->assertArrayHasKey('mock', Basset\Container::$shared);
	}

}