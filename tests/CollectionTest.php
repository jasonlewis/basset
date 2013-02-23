<?php

use Mockery as m;

class CollectionTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}
	

	public function testCanGetCollectionName()
	{
		$collection = $this->getStandardCollectionInstance();
		$this->assertEquals('foo', $collection->getName());
	}


	public function testCanAddBasicAsset()
	{
		$files = $this->getFiles();
		$files->shouldReceive('exists')->twice()->with('path/to/bar.css')->andReturn(true);
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
		$files->shouldReceive('exists')->twice()->with('path/to/bar.css')->andReturn(true);
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
		$directory = m::mock('JasonLewis\Basset\Directory[recursivelyIterateDirectory]', array($files, $manager, 'path/to/nested'));
		$directory->shouldReceive('recursivelyIterateDirectory')->once()->with('path/to/nested')->andReturn(array($file));
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
		$files->shouldReceive('exists')->twice()->with('path/to/nested/bar.css')->andReturn(true);
		$config = $this->getConfig();
		$config->shouldReceive('has')->once()->with('basset::assets.bar.css')->andReturn(false);
		$manager = new JasonLewis\Basset\AssetManager($files, 'path/to', 'local');
		$directory = m::mock('JasonLewis\Basset\Directory');
		$directory->shouldReceive('getPath')->twice()->andReturn('path/to/nested');
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
		$files = $this->getFiles();
		$files->shouldReceive('exists')->twice()->with('path/to/bar.css')->andReturn(true);
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


	public function testCanGetAllIgnoredAssets()
	{
		$files = $this->getFiles();
		$files->shouldReceive('exists')->twice()->with('path/to/bar.css')->andReturn(true);
		$files->shouldReceive('exists')->twice()->with('path/to/foo.css')->andReturn(true);
		$config = $this->getConfig();
		$config->shouldReceive('has')->once()->with('basset::assets.bar.css')->andReturn(false);
		$config->shouldReceive('has')->once()->with('basset::assets.foo.css')->andReturn(false);
		$manager = new JasonLewis\Basset\AssetManager($files, 'path/to', 'local');
		$collection = new JasonLewis\Basset\Collection($files, $config, $manager, 'foo');
		$collection->add('bar.css')->ignore();
		$collection->add('foo.css');
		$assets = $collection->getIgnoredAssets();
		$this->assertCount(1, $assets);
		$this->assertInstanceOf('JasonLewis\Basset\Asset', $assets[0]);
		$this->assertCount(2, $collection->getAssets());
	}


	public function testCanGetIgnoredAssetsByGroup()
	{
		$files = $this->getFiles();
		$files->shouldReceive('exists')->twice()->with('path/to/bar.css')->andReturn(true);
		$files->shouldReceive('exists')->twice()->with('path/to/foo.js')->andReturn(true);
		$config = $this->getConfig();
		$config->shouldReceive('has')->once()->with('basset::assets.bar.css')->andReturn(false);
		$config->shouldReceive('has')->once()->with('basset::assets.foo.js')->andReturn(false);
		$manager = m::mock('JasonLewis\Basset\AssetManager[getAbsolutePath]', array($files, 'path/to', 'local'));
		$manager->shouldReceive('getAbsolutePath')->with('path/to/bar.css')->andReturn('path/to/bar.css');
		$manager->shouldReceive('getAbsolutePath')->with('path/to/foo.js')->andReturn('path/to/foo.js');
		$collection = new JasonLewis\Basset\Collection($files, $config, $manager, 'foo');
		$collection->add('bar.css')->ignore();
		$collection->add('foo.js')->ignore();
		$assets = $collection->getIgnoredAssets('styles');
		$this->assertCount(1, $assets);
		$this->assertInstanceOf('JasonLewis\Basset\Asset', $assets[0]);
	}


	public function testCollectionIsCompiledCorrectly()
	{
		$files = $this->getFiles();
		$files->shouldReceive('exists')->twice()->with('path/to/css/example.css')->andReturn(true);
		$files->shouldReceive('exists')->twice()->with('path/to/js/example.js')->andReturn(true);
		$files->shouldReceive('getRemote')->once()->with('path/to/css/example.css')->andReturn('html { background-color: #fff; }');
		$files->shouldReceive('getRemote')->once()->with('path/to/js/example.js')->andReturn('alert("hello world")');
		$config = $this->getConfig();
		$config->shouldReceive('get')->twice()->with('basset::compile_remotes', '')->andReturn(false);
		$config->shouldReceive('has')->once()->with('basset::assets.css/example.css')->andReturn(false);
		$config->shouldReceive('has')->once()->with('basset::assets.js/example.js')->andReturn(false);
		$manager = m::mock('JasonLewis\Basset\AssetManager[getAbsolutePath]', array($files, 'path/to', 'local'));
		$manager->shouldReceive('getAbsolutePath')->with('path/to/css/example.css')->andReturn('path/to/css/example.css');
		$manager->shouldReceive('getAbsolutePath')->with('path/to/js/example.js')->andReturn('path/to/js/example.js');
		$instantiatedFilter = m::mock('Assetic\Filter\FilterInterface');
		$instantiatedFilter->shouldReceive('filterLoad')->once()->andReturn(null);
		$instantiatedFilter->shouldReceive('filterDump')->once()->andReturnUsing(function($asset)
		{
			$asset->setContent(str_replace('html', 'body', $asset->getContent()));
		});
		$filter = m::mock('JasonLewis\Basset\Filter');
		$filter->shouldReceive('getFilter')->times(3)->andReturn('BodyFilter');
		$filter->shouldReceive('getGroupRestriction')->twice()->andReturn('styles');
		$filter->shouldReceive('getEnvironments')->twice()->andReturn(array());
		$filter->shouldReceive('instantiate')->once()->andReturn($instantiatedFilter);
		$collection = new JasonLewis\Basset\Collection($files, $config, $manager, 'foo');
		$collection->add('css/example.css');
		$collection->add('js/example.js');
		$collection->apply($filter);
		$compiler = new JasonLewis\Basset\Compiler\StringCompiler($files, $config);
		$compiledCss = $compiler->compileStyles($collection);
		$compiledJs = $compiler->compileScripts($collection);
		$this->assertEquals('body { background-color: #fff; }', $compiledCss['path/to/css/example.css']);
		$this->assertEquals('alert("hello world")', $compiledJs['path/to/js/example.js']);
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