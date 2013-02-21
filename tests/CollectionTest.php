<?php

use Mockery as m;

class CollectionTest extends PHPUnit_Framework_TestCase {


	public function testCollectionInstanceIsCreated()
	{
		$collection = $this->getStandardCollectionInstance();
		$this->assertInstanceOf('JasonLewis\Basset\Collection', $collection);
		$this->assertEquals('foo', $collection->getName());
	}


	public function testCanAddBasicAsset()
	{
		$files = $this->getFiles();
		$files->shouldReceive('exists')->once()->with('path/to/bar.css')->andReturn(true);
		$config = $this->getConfig();
		$config->shouldReceive('has')->once()->with('basset::assets.bar.css')->andReturn(false);
		$manager = new JasonLewis\Basset\AssetManager($files, 'path/to', 'local');
		$collection = new JasonLewis\Basset\Collection($files, $config, $manager, 'foo');
		$collection->add('bar.css');
		$assets = $collection->getAssets();
		$this->assertCount(1, $assets);
		$this->assertInstanceOf('JasonLewis\Basset\Asset', $assets[0]);
	}


	public function testCanAddAliasedAsset()
	{
		$files = $this->getFiles();
		$files->shouldReceive('exists')->once()->with('path/to/bar.css')->andReturn(true);
		$config = $this->getConfig();
		$config->shouldReceive('has')->once()->with('basset::assets.foo')->andReturn(true);
		$config->shouldReceive('get')->once()->with('basset::assets.foo')->andReturn('bar.css');
		$manager = new JasonLewis\Basset\AssetManager($files, 'path/to', 'local');
		$collection = new JasonLewis\Basset\Collection($files, $config, $manager, 'foo');
		$collection->add('foo');
		$assets = $collection->getAssets();
		$this->assertCount(1, $assets);
		$this->assertInstanceOf('JasonLewis\Basset\Asset', $assets[0]);
	}


	public function testCanAddRemoteAssets()
	{
		$files = $this->getFiles();
		$config = $this->getConfig();
		$config->shouldReceive('has')->once()->with('basset::assets.http://foo.com/bar.css')->andReturn(false);
		$manager = new JasonLewis\Basset\AssetManager($files, 'path/to', 'local');
		$collection = new JasonLewis\Basset\Collection($files, $config, $manager, 'foo');
		$collection->add('http://foo.com/bar.css');
		$assets = $collection->getAssets();
		$this->assertCount(1, $assets);
		$this->assertInstanceOf('JasonLewis\Basset\Asset', $assets[0]);
		$this->assertTrue($assets[0]->isRemote());
	}


	public function testCanAddAssetsFromDefinedDirectory()
	{
		$files = $this->getFiles();
		$files->shouldReceive('exists')->once()->with('path/to/bar.css')->andReturn(false);
		$files->shouldReceive('exists')->once()->with('path/to/nested/bar.css')->andReturn(true);
		$config = $this->getConfig();
		$config->shouldReceive('has')->once()->with('basset::assets.bar.css')->andReturn(false);
		$config->shouldReceive('get')->once()->with('basset::directories')->andReturn(array('nested' => 'path/to/nested'));
		$manager = new JasonLewis\Basset\AssetManager($files, 'path/to', 'local');
		$file = m::mock('SplFileInfo');
		$file->shouldReceive('getRealPath')->once()->andReturn('path/to/nested/bar.css');
		$directory = m::mock('JasonLewis\Basset\Directory[recursivelyIterateDirectory]');
		$directory->shouldReceive('recursivelyIterateDirectory')->once()->andReturn(array($file));
		$collection = m::mock('JasonLewis\Basset\Collection[parseDirectoryPath]');
		$collection->shouldReceive('parseDirectoryPath')->with('path/to/nested')->andReturn($directory);
		$collection->__construct($files, $config, $manager, 'foo');
		$collection->add('bar.css');
		$assets = $collection->getAssets();
		$this->assertCount(1, $assets);
		$this->assertInstanceOf('JasonLewis\Basset\Asset', $assets[0]);
	}


	public function testCanAddAssetFromWithinWorkingDirectory()
	{
		$files = $this->getFiles();
		$files->shouldReceive('exists')->once()->with('path/to/bar.css')->andReturn(false);
		$files->shouldReceive('exists')->once()->with('path/to/nested/bar.css')->andReturn(true);
		$config = $this->getConfig();
		$config->shouldReceive('has')->once()->with('basset::assets.bar.css')->andReturn(false);
		$manager = new JasonLewis\Basset\AssetManager($files, 'path/to', 'local');
		$directory = m::mock('JasonLewis\Basset\Directory');
		$directory->shouldReceive('getPath')->once()->andReturn('path/to/nested');
		$collection = m::mock('JasonLewis\Basset\Collection[parseDirectoryPath]');
		$collection->shouldReceive('parseDirectoryPath')->with('path/to/nested')->andReturn($directory);
		$collection->__construct($files, $config, $manager, 'foo');
		$collection->directory('path/to/nested', function($collection)
		{
			$collection->add('bar.css');
		});
		$assets = $collection->getAssets();
		$this->assertCount(1, $assets);
		$this->assertInstanceOf('JasonLewis\Basset\Asset', $assets[0]);
	}


	public function testFiltersAreAppliedToCollection()
	{
		$collection = $this->getStandardCollectionInstance();

		$files = $this->getFiles();
		$files->shouldReceive('exists')->once()->with('path/to/bar.css')->andReturn(true);
		$config = $this->getConfig();
		$config->shouldReceive('has')->once()->with('basset::assets.bar.css')->andReturn(false);
		$manager = new JasonLewis\Basset\AssetManager($files, 'path/to', 'local');
		$collection = new JasonLewis\Basset\Collection($files, $config, $manager, 'foo');
		$collection->apply('FooFilter');
		$collection->add('bar.css');
		$filters = $collection->getFilters();
		$this->assertCount(1, $filters);
		$this->assertArrayHasKey('FooFilter', $filters);
		$collection->processCollection();
		$assets = $collection->getAssets();
		$this->assertCount(0, $collection->getFilters());
		$this->assertCount(1, $assets[0]->getFilters());
	}


	protected function getStandardCollectionInstance()
	{
		$files = $this->getFiles();
		$config = $this->getConfig();
		$manager = $this->getManager();
		return new JasonLewis\Basset\Collection($files, $config, $manager, 'foo');
	}


	protected function getFiles()
	{
		return m::mock('Illuminate\Filesystem\Filesystem');
	}


	protected function getConfig()
	{
		return m::mock('Illuminate\Config\Repository');
	}


	protected function getManager()
	{
		return m::mock('JasonLewis\Basset\AssetManager');
	}


}