<?php

use Mockery as m;

class AssetTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testAssetIsCreated()
	{
		$app = $this->getApplication();
		$asset = new Basset\Asset('path/to/foo', false, $app);
		$this->assertInstanceOf('Basset\Asset', $asset);
		$this->assertEquals('foo', $asset->getName());
		$this->assertEquals('css', $asset->getExtension());
		$this->assertTrue($asset->isValid());
	}


	public function testFiltersAreApplied()
	{
		$app = $this->getApplication();
		$app['config']->shouldReceive('has')->with('basset::filters.bar')->andReturn(true);
		$app['config']->shouldReceive('has')->with('basset::filters.Test\Filter')->andReturn(false);
		$app['config']->shouldReceive('get')->with('basset::filters.bar')->andReturn('FooBar');
		$asset = new Basset\Asset('path/to/foo', false, $app);
		$asset->apply('bar');
		$asset->apply('Test\Filter', array('option'));
		$filters = $asset->getFilters();
		$this->assertArrayHasKey('FooBar', $filters);
		$this->assertEquals(array('option'), $filters['Test\Filter']);
	}


	public function testAssetsAreCompiled()
	{
		$app = $this->getApplication();
		$asset = new Basset\Asset('path/to/foo', false, $app);
		$this->assertEquals('html { background-color: #fff; }', $asset->compile());
	}


	protected function getApplication()
	{
		$app = new Illuminate\Container;
		$app['files'] = m::mock('Illuminate\Filesystem');
		$app['files']->shouldReceive('extension')->once()->andReturn('css');
		$app['files']->shouldReceive('lastModified')->once()->andReturn(time());
		$app['files']->shouldReceive('getRemote')->once()->with('path/to/foo')->andReturn('html { background-color: #fff; }');
		$app['config'] = m::mock('stdClass');

		return $app;
	}


}