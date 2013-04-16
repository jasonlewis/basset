<?php

use Mockery as m;

class CollectionTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testCollectionIsCreated()
	{
		$app = $this->getApplication();
		$collection = new Basset\Collection('foo', $app);
		$this->assertInstanceOf('Basset\Collection', $collection);
		$this->assertEquals('foo', $collection->getName());
	}


	public function testBasicAssetsAreAddedToCollection()
	{
		$app = $this->getApplication();
		$app['files']->shouldReceive('exists')->once()->andReturn(true);
		$app['files']->shouldReceive('getRemote')->once()->andReturn('html { background-color: #fff; }');
		$app['files']->shouldReceive('extension')->once()->andReturn('css');
		$app['files']->shouldReceive('lastModified')->once()->andReturn(time());
		$app['config']->shouldReceive('has')->once()->with('basset::assets.sample.css')->andReturn(false);
		$collection = new Basset\Collection('foo', $app);
		$collection->add('sample.css');
		$this->assertNotEmpty($styles = $collection->getAssets('style'));
		$asset = array_pop($styles);
		$this->assertEquals('sample.css', $asset->getName());
	}


	public function testRemoteAssetsAreAddedToCollection()
	{
		$app = $this->getApplication();
		$app['files']->shouldReceive('getRemote')->once()->andReturn('html { background-color: #fff; }');
		$app['files']->shouldReceive('extension')->once()->andReturn('css');
		$app['config']->shouldReceive('has')->once()->with('basset::assets.http://example.com/foo.css')->andReturn(false);
		$collection = new Basset\Collection('foo', $app);
		$collection->add('http://example.com/foo.css');
		$this->assertNotEmpty($styles = $collection->getAssets('style'));
		$asset = array_pop($styles);
		$this->assertEquals('foo.css', $asset->getName());
	}


	public function testPathedAssetsAreAddedToCollection()
	{
		$app = $this->getApplication();
		$app['files']->shouldReceive('getRemote')->once()->andReturn('html { background-color: #fff; }');
		$app['files']->shouldReceive('extension')->once()->andReturn('css');
		$app['files']->shouldReceive('lastModified')->once()->andReturn(time());
		$app['config']->shouldReceive('has')->once()->with('basset::assets.path: full/path/to/sample.css')->andReturn(false);
		$collection = new Basset\Collection('foo', $app);
		$collection->add('path: full/path/to/sample.css');
		$this->assertNotEmpty($styles = $collection->getAssets('style'));
		$asset = array_pop($styles);
		$this->assertEquals('sample.css', $asset->getName());
		$this->assertEquals('full/path/to/sample.css', $asset->getPath());
	}


	public function testAssetsFromConfiguredDirectoryAreAddedToCollection()
	{
		$app = $this->getApplication();
		$app['config']->shouldReceive('get')->with('basset::directories')->andReturn(array('bar' => 'a/real/path'));
		$app['files']->shouldReceive('exists')->once()->andReturn(false);
		$app['files']->shouldReceive('getRemote')->once()->andReturn('html { background-color: #fff; }');
		$app['files']->shouldReceive('extension')->once()->andReturn('css');
		$app['files']->shouldReceive('lastModified')->once()->andReturn(time());
		$app['config']->shouldReceive('has')->once()->with('basset::assets.sample.css')->andReturn(false);
		$file = m::mock('stdClass');
		$file->shouldReceive('getRealPath')->once()->andReturn('a/real/path/to/sample.css');
		$file->shouldReceive('getPathname')->once()->andReturn('path/to/sample.css');
		$directory = m::mock('Basset\Directory');
		$directory->shouldReceive('recursivelyIterateDirectory')->once()->andReturn(array($file));
		$directory->shouldReceive('getPath')->once()->andReturn('a/real/path/to');
		$collection = m::mock('Basset\Collection[parseDirectory]');
		$collection->shouldReceive('parseDirectory')->once()->with('a/real/path')->andReturn($directory);
		$collection->__construct('foo', $app);
		$collection->add('sample.css');
		$assets = $collection->getAssets('style');
		$this->assertEquals('sample.css', $assets[0]->getName());
		$this->assertEquals('path/to/sample.css', $assets[0]->getPath());
	}


	public function testAliasedAssetsAreAddedToCollection()
	{
		$app = $this->getApplication();
		$app['files']->shouldReceive('exists')->once()->andReturn(true);
		$app['files']->shouldReceive('getRemote')->once()->andReturn('html { background-color: #fff; }');
		$app['files']->shouldReceive('extension')->once()->andReturn('css');
		$app['files']->shouldReceive('lastModified')->once()->andReturn(time());
		$app['config']->shouldReceive('has')->once()->with('basset::assets.sample')->andReturn(true);
		$app['config']->shouldReceive('get')->once()->with('basset::assets.sample')->andReturn('path/to/sample.css');
		$collection = new Basset\Collection('foo', $app);
		$collection->add('sample');
		$this->assertNotEmpty($styles = $collection->getAssets('style'));
		$asset = array_pop($styles);
		$this->assertEquals('sample.css', $asset->getName());
	}


	public function testDirectoryIsAddedToCollection()
	{
		$app = $this->getApplication();
		$asset = m::mock('Basset\Asset');
		$asset->shouldReceive('getGroup')->once()->andReturn('style');
		$directory = m::mock('Basset\Directory');
		$directory->shouldReceive('requireDirectory')->once()->andReturn($directory);
		$directory->shouldReceive('getPending')->once()->andReturn(array($asset));
		$collection = m::mock('Basset\Collection[parseDirectory]');
		$collection->shouldReceive('parseDirectory')->once()->andReturn($directory);
		$collection->requireDirectory('foo');
		$this->assertNotEmpty($styles = $collection->getAssets('style'));
		$this->assertCount(1, $styles);
	}


	public function testDirectoryTreeIsAddedToCollection()
	{
		$app = $this->getApplication();
		$asset = m::mock('Basset\Asset');
		$asset->shouldReceive('getGroup')->once()->andReturn('style');
		$directory = m::mock('Basset\Directory');
		$directory->shouldReceive('requireTree')->once()->andReturn($directory);
		$directory->shouldReceive('getPending')->once()->andReturn(array($asset));
		$collection = m::mock('Basset\Collection[parseDirectory]');
		$collection->shouldReceive('parseDirectory')->once()->andReturn($directory);
		$collection->requireTree('foo');
		$this->assertNotEmpty($styles = $collection->getAssets('style'));
		$this->assertCount(1, $styles);
	}


	public function testCanGetCompiledName()
	{
		$app = $this->getApplication();
		$app['files']->shouldReceive('exists')->once()->andReturn(true);
		$app['files']->shouldReceive('getRemote')->once()->andReturn('html { background-color: #fff; }');
		$app['files']->shouldReceive('extension')->once()->andReturn('css');
		$app['files']->shouldReceive('lastModified')->once()->andReturn(time());
		$app['config']->shouldReceive('has')->once()->with('basset::assets.sample.css')->andReturn(false);
		$collection = new Basset\Collection('foo', $app);
		$asset = $collection->add('sample.css');
		$this->assertEquals(md5($asset->getLastModified()), $collection->getFingerprint('style'));
		$this->assertEquals('foo-'.md5($asset->getLastModified()).'.css', $collection->getCompiledName('style'));
	}


	public function testCompiledNameChangesWithFilters()
	{
		$app = $this->getApplication();
		$app['files']->shouldReceive('exists')->once()->andReturn(true);
		$app['files']->shouldReceive('getRemote')->once()->andReturn('html { background-color: #fff; }');
		$app['files']->shouldReceive('extension')->once()->andReturn('css');
		$app['files']->shouldReceive('lastModified')->once()->andReturn(time());
		$app['config']->shouldReceive('has')->once()->with('basset::filters.SomeFilter')->andReturn(false);
		$app['config']->shouldReceive('has')->once()->with('basset::assets.sample.css')->andReturn(false);
		$collection = new Basset\Collection('foo', $app);
		$asset = $collection->add('sample.css');
		$this->assertEquals(md5($asset->getLastModified()), $collection->getFingerprint('style'));
		$this->assertEquals('foo-'.md5($asset->getLastModified()).'.css', $collection->getCompiledName('style'));
		$collection->apply('SomeFilter');
		$this->assertEquals(md5($asset->getLastModified().PHP_EOL.'SomeFilter'), $collection->getFingerprint('style'));
		$this->assertEquals('foo-'.md5($asset->getLastModified().PHP_EOL.'SomeFilter').'.css', $collection->getCompiledName('style'));
	}

	public function testAssetsAreAddedToCollectionThroughDirectory()
	{
		$app = $this->getApplication();
		$app['files']->shouldReceive('getRemote')->once()->andReturn('html { background-color: #fff; }');
		$app['files']->shouldReceive('extension')->once()->andReturn('css');
		$app['files']->shouldReceive('lastModified')->once()->andReturn(time());
		$app['config']->shouldReceive('has')->once()->with('basset::assets.foo.css')->andReturn(false);

		$collection = new Basset\Collection('foo', $app);
		$collection->directory('foo/bar', function ($asset)
		{
			$asset->add('foo.css');
		});
		$this->assertNotEmpty($styles = $collection->getAssets('style'));
		$asset = array_pop($styles);
		$this->assertEquals('foo.css', $asset->getName());
		$this->assertEquals('path/to/public/foo/bar/foo.css', $asset->getPath());
	}


	public function testCollectionIsCompiled()
	{
		$app = $this->getApplication();
		$app['files']->shouldReceive('exists')->once()->andReturn(true);
		$app['files']->shouldReceive('getRemote')->once()->andReturn('html { background-color: #fff; }');
		$app['files']->shouldReceive('extension')->once()->andReturn('css');
		$app['files']->shouldReceive('lastModified')->once()->andReturn(time());
		$app['config']->shouldReceive('has')->once()->with('basset::assets.sample.css')->andReturn(false);
		$collection = new Basset\Collection('foo', $app);
		$collection->add('sample.css');
		$this->assertEquals('html { background-color: #fff; }', $collection->compile('style'));
	}


	public function testFiltersAreAppliedToEntireCollection()
	{
		$app = $this->getApplication();
		$app['config']->shouldReceive('get')->with('basset::filters')->andReturn(array());
		$app['config']->shouldReceive('has')->with('basset::filters.FooFilter')->andReturn(false);
		$app['files']->shouldReceive('getRemote')->twice()->andReturn('html { }', 'body { }');
		$app['files']->shouldReceive('lastModified')->twice()->andReturn(time());
		$app['files']->shouldReceive('extension')->twice()->andReturn('css');
		$assets = array(
			new Basset\Asset('path/to/foo', $app),
			new Basset\Asset('path/to/bar', $app)
		);
		$directory = m::mock('Basset\Directory');
		$directory->shouldReceive('requireTree')->once()->andReturn($directory);
		$directory->shouldReceive('getPending')->once()->andReturn($assets);
		$collection = m::mock('Basset\Collection[parseDirectory]');
		$collection->shouldReceive('parseDirectory')->once()->andReturn($directory);
		$collection->requireTree('foo');
		$collection->apply('FooFilter', array('option'));
		$assets = $collection->getAssets('style');
		$filters = $assets[0]->getFilters();
		$this->assertArrayHasKey('FooFilter', $filters);
		$this->assertEquals(array('option'), $filters['FooFilter']);
		$filters = $assets[1]->getFilters();
		$this->assertArrayHasKey('FooFilter', $filters);
		$this->assertEquals(array('option'), $filters['FooFilter']);
	}


	protected function getApplication()
	{
		$app = new Illuminate\Container\Container;
		$app['files'] = m::mock('Illuminate\Filesystem\Filesystem');
		$app['config'] = m::mock('stdClass');
		$app['path.public'] = 'path/to/public';
		return $app;
	}


}