<?php

class BassetTest extends PHPUnit_Framework_TestCase {

	/**
	 * Starts basset and creates the mock directory. Not ideal but I'm too lazy to try and
	 * work in a virtual directory system.
	 * 
	 * @return void
	 */
	public function setUp()
	{
		Bundle::start('basset');

		if(!file_exists(__DIR__ . '/mock'))
		{
			mkdir(__DIR__ . '/mock');
		}
	}

	/**
	 * If the mock directory exists we'll delete it in the tear down.
	 * 
	 * @return void
	 */
	public function tearDown()
	{
		if(file_exists(__DIR__ . '/mock'))
		{
			rmdir(__DIR__ . '/mock');
		}
	}

	/**
	 * Tests that a container can be created.
	 * 
	 * @return void
	 */
	public function testContainersCanBeCreated()
	{
		$container = new Basset\Container(null);

		$this->assertTrue($container == new Basset\Container(null));
		$this->assertInstanceOf('Basset\\Container', $container);
	}

	/**
	 * Tests that style routes can be created.
	 * 
	 * @return void
	 */
	public function testStyleRoutesCanBeCreated()
	{
		Basset::styles('mock', function($basset){});

		$route = Bundle::option('basset', 'handles') . '/mock.css';

		$this->routesCanBeCreated($route);
	}

	/**
	 * Tests that script routes can be created.
	 * 
	 * @return void
	 */
	public function testScriptRoutesCanBeCreated()
	{
		Basset::scripts('mock', function($basset){});

		$route = Bundle::option('basset', 'handles') . '/mock.js';

		$this->routesCanBeCreated($route);
	}

	/**
	 * Tests that the routes are created within the Laravel routing system.
	 * 
	 * @return void
	 */
	private function routesCanBeCreated($route)
	{
		$this->assertArrayHasKey($route, Basset::$routes);
		$this->assertInstanceOf('Laravel\\Routing\\Route', Laravel\Routing\Router::route('GET', $route));
	}

	/**
	 * Tests that inline containers can be created.
	 * 
	 * @return void
	 */
	public function testInlineContainerCanBeCreated()
	{
		$container = Basset::inline('mock');

		$this->assertTrue($container === Basset::inline('mock'));
		$this->assertArrayHasKey('inline::mock', Basset::$inline);
	}

	/**
	 * Tests that assets are compiled.
	 * 
	 * @return void
	 */
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

	/**
	 * Tests that a Basset URL can be created.
	 * 
	 * @return void
	 */
	public function testBassetURLGenerated()
	{
		Basset::styles('mock', function($basset){});

		$route = URL::to(Bundle::option('basset', 'handles') . '/mock.css');

		$this->assertTrue(HTML::style($route) === Basset::show('mock.css'));
	}

	/**
	 * Tests that assets can be shared.
	 * 
	 * @return void
	 */
	public function testAssetsCanBeShared()
	{
		Basset::share('mock', 'mock.css');

		$this->assertArrayHasKey('mock', Basset\Container::$shared);
	}

}