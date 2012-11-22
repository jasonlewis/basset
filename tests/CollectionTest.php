<?php

use Mockery as m;

class CollectionTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testCanCreateCollection()
	{
		$app = new Illuminate\Container;
		$app['files'] = m::mock('Illuminate\Filesystem');
		$app['config'] = m::mock('Illuminate\Config\Repository');
		$collection = new Basset\Collection('foo', $app);
		$this->assertInstanceOf('Basset\Collection', $collection);
		$this->assertEquals('foo', $collection->getName());
	}


	public function testCanAddAssetToCollection()
	{
		$app = new Illuminate\Container;
		$app['files'] = m::mock('Illuminate\Filesystem');
		$app['config'] = new Illuminate\Config\Repository(m::mock('Illuminate\Config\LoaderInterface'), 'production');
		$app['config']->getLoader()->shouldReceive('load')->once()->with('production', 'directories', 'basset')->andReturn(array('foo' => 'path: '.__DIR__.'/fixtures'));
		$app['config']->getLoader()->shouldReceive('exists')->once()->andReturn(true);
		$collection = new Basset\Collection('foo', $app);
		$collection->add('sample.css');
		$this->assertNotEmpty($styles = $collection->getAssets('style'));
		$this->assertEquals('sample.css', $styles[0]->getName());
	}


	public function testCanAddDirectoryToCollection()
	{
		$app = new Illuminate\Container;
		$app['files'] = m::mock('Illuminate\Filesystem');
		$app['config'] = new Illuminate\Config\Repository(m::mock('Illuminate\Config\LoaderInterface'), 'production');
		$app['config']->getLoader()->shouldReceive('load')->once()->with('production', 'basset', 'basset')->andReturn(array('directories' => array('foo' => 'path: '.__DIR__.'/fixtures')));
		$collection = new Basset\Collection('foo', $app);
		$collection->requireDirectory('foo');
		$this->assertNotEmpty($styles = $collection->getAssets('style'));
		$this->assertCount(2, $styles);
	}


	public function testCanAddDirectoryTreeToCollection()
	{
		$app = new Illuminate\Container;
		$app['files'] = m::mock('Illuminate\Filesystem');
		$app['config'] = new Illuminate\Config\Repository(m::mock('Illuminate\Config\LoaderInterface'), 'production');
		$collection = new Basset\Collection('foo', $app);
		$collection->requireTree('path: '.__DIR__);
		$this->assertNotEmpty($styles = $collection->getAssets('style'));
		$this->assertCount(2, $styles);
	}


	public function testCanGetCompiledName()
	{
		$app = new Illuminate\Container;
		$app['files'] = m::mock('Illuminate\Filesystem');
		$app['config'] = new Illuminate\Config\Repository(m::mock('Illuminate\Config\LoaderInterface'), 'production');
		$app['config']->getLoader()->shouldReceive('load')->once()->with('production', 'directories', 'basset')->andReturn(array('foo' => 'path: '.__DIR__.'/fixtures'));
		$app['config']->getLoader()->shouldReceive('exists')->once()->andReturn(true);
		$collection = new Basset\Collection('foo', $app);
		$collection->add('sample.css');
		$this->assertEquals(md5(filemtime(__DIR__.'/fixtures/sample.css')), $collection->getFingerprint('style'));
		$this->assertEquals('foo-'.md5(filemtime(__DIR__.'/fixtures/sample.css')).'.css', $collection->getCompiledName('style'));
	}


	public function testCanCompileCollection()
	{
		$app = new Illuminate\Container;
		$app['files'] = m::mock('Illuminate\Filesystem');
		$app['config'] = new Illuminate\Config\Repository(m::mock('Illuminate\Config\LoaderInterface'), 'production');
		$app['config']->getLoader()->shouldReceive('load')->once()->with('production', 'directories', 'basset')->andReturn(array('foo' => 'path: '.__DIR__.'/fixtures'));
		$app['config']->getLoader()->shouldReceive('exists')->once()->andReturn(true);
		$collection = new Basset\Collection('foo', $app);
		$collection->add('sample.css');
		$this->assertEquals('html { background-color: #fff; }', $collection->compile('style'));
	}


}