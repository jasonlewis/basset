<?php

use Mockery as m;

class AssetTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testCanCreateAsset()
	{
		$app = $this->getApplication();
		$asset = new Basset\Asset('path/to/foo', $app);
		$this->assertInstanceOf('Basset\Asset', $asset);
		$this->assertEquals('foo', $asset->getName());
		$this->assertEquals('css', $asset->getExtension());
		$this->assertTrue($asset->isValid());
	}


	public function testCanApplyFilter()
	{
		$app = $this->getApplication();
		$app['config']->getLoader()->shouldReceive('load')->once()->with('production', 'filters', 'basset')->andReturn(array('bar' => 'FooBar'));
		$asset = new Basset\Asset('path/to/foo', $app);
		$asset->apply('bar');
		$asset->apply('Test\Filter', array('option'));
		$filters = $asset->getFilters();
		$this->assertArrayHasKey('FooBar', $filters);
		$this->assertEquals(array('option'), $filters['Test\Filter']);
	}


	public function testCanCompileAssets()
	{
		$app = $this->getApplication();
		$asset = new Basset\Asset('path/to/foo', $app);
		$this->assertEquals('html { background-color: #fff; }', $asset->compile());
	}


	protected function getApplication()
	{
		$app = new Illuminate\Container;
		$app['files'] = m::mock('Illuminate\Filesystem');
		$app['files']->shouldReceive('extension')->once()->andReturn('css');
		$app['files']->shouldReceive('lastModified')->once()->andReturn(time());
		$app['files']->shouldReceive('get')->once()->with('path/to/foo')->andReturn('html { background-color: #fff; }');
		$app['config'] = new Illuminate\Config\Repository(m::mock('Illuminate\Config\LoaderInterface'), 'production');

		return $app;
	}


}