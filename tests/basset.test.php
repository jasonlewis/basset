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

		// Empty existing routes from the application
		Basset::$routes = array();

		if(!file_exists(__DIR__ . '/mock'))
		{
			\Laravel\File::mkdir(__DIR__ . '/mock');
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
			\Laravel\File::cleandir(__DIR__ . '/mock');
		}
	}

	/**
	 * Creates a fake stylesheet to use during mocking
	 *
	 * @param  string $stylesheet The stylesheet name
	 * @return array              Path to the created file, its content
	 */
	public function createFakeStylesheet($stylesheet = 'mock')
	{
		\Laravel\File::put($file = __DIR__ . '/mock/' .$stylesheet. '.css', $contents = 'body { background-color: #ff0000; }');

		return array($file, $contents);
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

		$this->assertEquals($container, Basset::inline('mock'));
		$this->assertArrayHasKey('inline::mock', Basset::$inline);
	}

	/**
	 * Tests that assets are compiled.
	 *
	 * @return void
	 */
	public function testAssetsCanBeCompiled()
	{
		list($file, $contents) = $this->createFakeStylesheet();

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

		$this->assertEquals(trim($compiled), $contents);
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

		$this->assertEquals(HTML::style($route), Basset::show('mock.css'));
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