<?php

use Mockery as m;

class AssetTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testCanCreateAsset()
	{
		$app = new Illuminate\Container;
		$app['files'] = m::mock('Illuminate\Filesystem');
		$app['config'] = m::mock('Illuminate\Config\Repository');
		$asset = new Basset\Asset($this->generateTestFile(), 'path/to/directory', $app);
		$this->assertInstanceOf('Basset\Asset', $asset);
		$this->assertEquals('foo', $asset->getName());
		$this->assertEquals('css', $asset->getExtension());
		$this->assertTrue($asset->isValid());
	}


	public function testCanApplyFilter()
	{
		$app = new Illuminate\Container;
		$app['files'] = m::mock('Illuminate\Filesystem');
		$app['config'] = new Illuminate\Config\Repository(m::mock('Illuminate\Config\LoaderInterface'), 'production');
		$app['config']->getLoader()->shouldReceive('load')->once()->with('production', 'filters', 'basset')->andReturn(array('bar' => 'FooBar'));
		$asset = new Basset\Asset($this->generateTestFile(), 'path/to/directory', $app);
		$asset->apply('bar');
		$asset->apply('Test\Filter', array('option'));
		$filters = $asset->getFilters();
		$this->assertArrayHasKey('FooBar', $filters);
		$this->assertEquals(array('option'), $filters['Test\Filter']);
	}


	public function testCanCompileAssets()
	{
		$app = new Illuminate\Container;
		$app['files'] = m::mock('Illuminate\Filesystem');
		$app['config'] = m::mock('Illuminate\Config\Repository');
		$asset = new Basset\Asset(new SplFileInfo(__DIR__.'/fixtures/sample.css'), __DIR__.'/fixtures', $app);
		$this->assertEquals('html { background-color: #fff; }', $asset->compile());
	}


	protected function generateTestFile()
	{
		$file = $this->getMock('SplFileInfo', array('__construct', 'getRelativePath', 'getFilename', 'getPathname', 'getExtension', 'getMTime'), array('foo'));
		$file->expects($this->any())->method('getFilename')->will($this->returnValue('foo'));
		$file->expects($this->any())->method('getPathname')->will($this->returnValue('path/to/foo'));
		$file->expects($this->any())->method('getExtension')->will($this->returnValue('css'));
		$file->expects($this->any())->method('getMTime')->will($this->returnValue(time()));
		$file->expects($this->any())->method('getRelativePath')->will($this->returnValue('foo.css'));
		return $file;
	}


}