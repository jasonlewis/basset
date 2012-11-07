<?php

use Mockery as m;
use Basset\Collection;

class CollectionTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testCanCreateCollection()
	{
		$config = m::mock('Illuminate\Config\Repository');
		$files = m::mock('Illuminate\Filesystem');

		$collection = new Collection('foo', $files, $config);

		$this->assertInstanceOf('Basset\Collection', $collection);
		$this->assertEquals('foo', $collection->getName());
	}


	public function testCanAddAssetToCollection()
	{
		$files = m::mock('Illuminate\Filesystem');
		$config = new Illuminate\Config\Repository(m::mock('Illuminate\Config\LoaderInterface'), 'production');

		$config->getLoader()->shouldReceive('load')->once()->with('production', 'basset', null)->andReturn(array(
			'directories' => array('foo' => 'path: '.__DIR__.'/fixtures')
		));

		$collection = new Collection('foo', $files, $config);

		$collection->add('sample.css');

		$this->assertNotEmpty($styles = $collection->getAssets('style'));
		$this->assertEquals('sample.css', $styles[0]->getName());
	}


	public function testCanAddDirectoryToCollection()
	{
		$files = m::mock('Illuminate\Filesystem');
		$config = new Illuminate\Config\Repository(m::mock('Illuminate\Config\LoaderInterface'), 'production');

		$config->getLoader()->shouldReceive('load')->once()->with('production', 'basset', null)->andReturn(array(
			'directories' => array('foo' => 'path: '.__DIR__.'/fixtures')
		));

		$collection = new Collection('foo', $files, $config);

		$collection->requireDirectory('foo');

		$this->assertNotEmpty($styles = $collection->getAssets('style'));
		$this->assertEquals('sample-exclude.css', $styles[0]->getName());
		$this->assertEquals('sample.css', $styles[1]->getName());
	}


	public function testCanAddDirectoryTreeToCollection()
	{
		$files = m::mock('Illuminate\Filesystem');
		$config = new Illuminate\Config\Repository(m::mock('Illuminate\Config\LoaderInterface'), 'production');

		$collection = new Collection('foo', $files, $config);

		$collection->requireTree('path: '.__DIR__);

		$this->assertNotEmpty($styles = $collection->getAssets('style'));
		$this->assertEquals('sample-exclude.css', $styles[0]->getName());
		$this->assertEquals('sample.css', $styles[1]->getName());
	}


	public function testCanGetCompiledName()
	{
		$files = m::mock('Illuminate\Filesystem');
		$config = new Illuminate\Config\Repository(m::mock('Illuminate\Config\LoaderInterface'), 'production');

		$config->getLoader()->shouldReceive('load')->once()->with('production', 'basset', null)->andReturn(array(
			'directories' => array('foo' => 'path: '.__DIR__.'/fixtures')
		));

		$collection = new Collection('foo', $files, $config);

		$collection->add('sample.css');

		$this->assertEquals(md5('sample.css'), $collection->getFingerprint('style'));
		$this->assertEquals('foo-'.md5('sample.css').'.css', $collection->getCompiledName('style'));
	}


	public function testCanCompileCollection()
	{
		$files = m::mock('Illuminate\Filesystem');
		$config = new Illuminate\Config\Repository(m::mock('Illuminate\Config\LoaderInterface'), 'production');

		$config->getLoader()->shouldReceive('load')->once()->with('production', 'basset', null)->andReturn(array(
			'directories' => array('foo' => 'path: '.__DIR__.'/fixtures')
		));

		$collection = new Collection('foo', $files, $config);

		$collection->add('sample.css');

		$this->assertEquals('html { background-color: #fff; }', $collection->compile('style'));
	}


}