<?php

use Mockery as m;
use Basset\Collection;
use Basset\AssetFinder;
use Basset\Factory\Manager;
use Basset\Factory\AssetFactory;
use Basset\Factory\FilterFactory;
use Basset\Builder\StringBuilder;

class CollectionTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testGetCollectionName()
    {
        $collection = $this->getCollectionInstance();
        $this->assertEquals('foo', $collection->getName());
    }


    public function testAddingValidAssetDoesNotThrowException()
    {
        $collection = $this->getCollectionInstance();

        $collection->getFinder()->shouldReceive('withinWorkingDirectory')->once()->andReturn(false);
        $collection->getFinder()->shouldReceive('find')->once()->with('bar.css')->andReturn('foo/bar.css');
        $collection->getFactory()->get('asset')->shouldReceive('make')->once()->with('foo/bar.css')->andReturn($asset = $this->getAssetMock());

        $asset->shouldReceive('isRemote')->once()->andReturn(false);

        $this->assertInstanceOf('Basset\Asset', $collection->add('bar.css'));
    }


    public function testAddingValidRemoteAssetExcludesAsset()
    {
        $collection = $this->getCollectionInstance();

        $collection->getFinder()->shouldReceive('withinWorkingDirectory')->once()->andReturn(false);
        $collection->getFinder()->shouldReceive('find')->once()->with('bar.css')->andReturn('foo/bar.css');
        $collection->getFactory()->get('asset')->shouldReceive('make')->once()->with('foo/bar.css')->andReturn($asset = $this->getAssetMock());

        $asset->shouldReceive('isRemote')->once()->andReturn(true);
        $asset->shouldReceive('exclude')->once()->andReturn(true);

        $this->assertInstanceOf('Basset\Asset', $collection->add('bar.css'));
    }


    public function testAddingInvalidAssetThrowsException()
    {
        $collection = $this->getCollectionInstance();

        $collection->getFinder()->shouldReceive('find')->once()->with('bar.css')->andThrow('Basset\Exception\AssetNotFoundException');
        $collection->getFactory()->get('asset')->shouldReceive('make')->once()->with(null)->andReturn($asset = $this->getAssetMock());

        $this->assertInstanceOf('Basset\Asset', $collection->add('bar.css'));
    }


    public function testAddingAssetWithCallback()
    {
        $collection = $this->getCollectionInstance();

        $collection->getFinder()->shouldReceive('withinWorkingDirectory')->once()->andReturn(false);
        $collection->getFinder()->shouldReceive('find')->once()->with('bar.css')->andReturn('foo/bar.css');
        $collection->getFactory()->get('asset')->shouldReceive('make')->once()->with('foo/bar.css')->andReturn($asset = $this->getAssetMock());
        
        $asset->shouldReceive('isRemote')->once()->andReturn(false);
        $asset->shouldReceive('fireInCallback')->once();

        $collection->add('bar.css', function($asset)
        {
            $asset->fireInCallback();
        });
    }


    public function testAddingAssetFromWithinWorkingDirectory()
    {
        $collection = $this->getCollectionInstance();

        $collection->getFinder()->shouldReceive('setWorkingDirectory')->once()->with('foo');
        $collection->getFinder()->shouldReceive('getWorkingDirectory')->once()->andReturn('foo');
        $collection->getFinder()->shouldReceive('resetWorkingDirectory')->once();

        $collection->getFactory()->get('directory')->shouldReceive('make')->once()->with('foo')->andReturn($directory = $this->getDirectoryMock());

        $collection->getFinder()->shouldReceive('withinWorkingDirectory')->once()->andReturn(true);
        $collection->getFinder()->shouldReceive('find')->once()->with('bar.css')->andReturn('foo/bar.css');
        $collection->getFactory()->get('asset')->shouldReceive('make')->once()->with('foo/bar.css')->andReturn($asset = $this->getAssetMock());

        $directory->shouldReceive('add')->once()->with($asset);

        $asset->shouldReceive('isRemote')->once()->andReturn(false);

        $collection->directory('foo', function($collection)
        {
            $collection->add('bar.css');
        });
    }


    public function testChangeCollectionWorkingDirectory()
    {
        $collection = $this->getCollectionInstance();

        $collection->getFinder()->shouldReceive('setWorkingDirectory')->once()->with('foo');
        $collection->getFinder()->shouldReceive('getWorkingDirectory')->once()->andReturn('foo');
        $collection->getFinder()->shouldReceive('resetWorkingDirectory')->once();

        $collection->getFactory()->get('directory')->shouldReceive('make')->once()->with('foo')->andReturn($this->getDirectoryMock());

        $this->assertInstanceOf('Basset\Directory', $directory = $collection->directory('foo'));
        $this->assertCount(1, $collection->getDirectories());
    }


    public function testChangeCollectionToInvalidWorkingDirectoryThrowsException()
    {
        $collection = $this->getCollectionInstance();

        $collection->getFinder()->shouldReceive('setWorkingDirectory')->once()->with('foo')->andThrow('Basset\Exception\DirectoryNotFoundException');
        $collection->getFactory()->get('directory')->shouldReceive('make')->once()->with(null)->andReturn($this->getDirectoryMock());

        $this->assertInstanceOf('Basset\Directory', $directory = $collection->directory('foo'));
    }


    public function testChangeCollectionWorkingDirectoryAndFireDirectoryCallback()
    {
        $collection = $this->getCollectionInstance();

        $collection->getFinder()->shouldReceive('setWorkingDirectory')->once()->with('foo');
        $collection->getFinder()->shouldReceive('getWorkingDirectory')->once()->andReturn('foo');
        $collection->getFinder()->shouldReceive('resetWorkingDirectory')->once();

        $collection->getFactory()->get('directory')->shouldReceive('make')->once()->with('foo')->andReturn($this->getDirectoryMock());

        $fired = false;
        $tester = $this;

        $this->assertInstanceOf('Basset\Directory', $directory = $collection->directory('foo', function($collection) use (&$fired, $tester)
        {
            $fired = true;
            $tester->assertEquals('foo', $collection->getName());
        }));
        $this->assertTrue($fired);
    }


    public function testRequiringDirectoryFiresCorrectMethodsAndReturnsDirectoryInstance()
    {
        $collection = $this->getCollectionInstance();

        $collection->getFinder()->shouldReceive('setWorkingDirectory')->once()->with('foo');
        $collection->getFinder()->shouldReceive('getWorkingDirectory')->once()->andReturn('foo');
        $collection->getFinder()->shouldReceive('resetWorkingDirectory')->once();

        $collection->getFactory()->get('directory')->shouldReceive('make')->once()->with('foo')->andReturn($directory = $this->getDirectoryMock());

        $directory->shouldReceive('requireDirectory')->once()->andReturn(m::self());

        $this->assertInstanceOf('Basset\Directory', $collection->requireDirectory('foo'));
    }


    public function testRequiringTreeFiresCorrectMethodsAndReturnsDirectoryInstance()
    {
        $collection = $this->getCollectionInstance();

        $collection->getFinder()->shouldReceive('setWorkingDirectory')->once()->with('foo');
        $collection->getFinder()->shouldReceive('getWorkingDirectory')->once()->andReturn('foo');
        $collection->getFinder()->shouldReceive('resetWorkingDirectory')->once();

        $collection->getFactory()->get('directory')->shouldReceive('make')->once()->with('foo')->andReturn($directory = $this->getDirectoryMock());

        $directory->shouldReceive('requireTree')->once()->andReturn(m::self());

        $this->assertInstanceOf('Basset\Directory', $collection->requireTree('foo'));
    }


    public function testGetAssetsWithNoOrdering()
    {
        $assets = array();

        $collection = $this->getCollectionInstance();

        $collection->getFinder()->shouldReceive('withinWorkingDirectory')->twice()->andReturn(false);
        $collection->getFinder()->shouldReceive('find')->once()->with('bar.css')->andReturn('foo/bar.css');
        $collection->getFinder()->shouldReceive('find')->once()->with('baz.css')->andReturn('foo/baz.css');
        $collection->getFactory()->get('asset')->shouldReceive('make')->once()->with('foo/bar.css')->andReturn($assets['bar'] = $this->getAssetMock());
        $collection->getFactory()->get('asset')->shouldReceive('make')->once()->with('foo/baz.css')->andReturn($assets['baz'] = $this->getAssetMock());

        $assets['bar']->shouldReceive('isRemote')->once()->andReturn(false);
        $assets['bar']->shouldReceive('getOrder')->once()->andReturn(null);

        $assets['baz']->shouldReceive('isRemote')->once()->andReturn(false);
        $assets['baz']->shouldReceive('getOrder')->once()->andReturn(null);

        $collection->add('bar.css');
        $collection->add('baz.css');

        $this->assertEquals(array_values($assets), $collection->getAssets());
    }


    public function testGetAssetsWithOrdering()
    {
        $assets = array();

        $collection = $this->getCollectionInstance();

        $collection->getFinder()->shouldReceive('withinWorkingDirectory')->twice()->andReturn(false);
        $collection->getFinder()->shouldReceive('find')->once()->with('bar.css')->andReturn('foo/bar.css');
        $collection->getFinder()->shouldReceive('find')->once()->with('baz.css')->andReturn('foo/baz.css');
        $collection->getFactory()->get('asset')->shouldReceive('make')->once()->with('foo/bar.css')->andReturn($assets['bar'] = $this->getAssetMock());
        $collection->getFactory()->get('asset')->shouldReceive('make')->once()->with('foo/baz.css')->andReturn($assets['baz'] = $this->getAssetMock());

        $assets['bar']->shouldReceive('isRemote')->once()->andReturn(false);
        $assets['bar']->shouldReceive('getOrder')->once()->andReturn(null);

        $assets['baz']->shouldReceive('isRemote')->once()->andReturn(false);
        $assets['baz']->shouldReceive('getOrder')->once()->andReturn(1);

        $collection->add('bar.css');
        $collection->add('baz.css');

        $this->assertEquals(array_reverse(array_values($assets)), $collection->getAssets());
    }


    public function testGetAssetsWithSpecificGroup()
    {
        $assets = array();

        $collection = $this->getCollectionInstance();

        $collection->getFinder()->shouldReceive('withinWorkingDirectory')->twice()->andReturn(false);
        $collection->getFinder()->shouldReceive('find')->once()->with('bar.css')->andReturn('foo/bar.css');
        $collection->getFinder()->shouldReceive('find')->once()->with('baz.js')->andReturn('foo/baz.js');
        $collection->getFactory()->get('asset')->shouldReceive('make')->once()->with('foo/bar.css')->andReturn($assets['bar'] = $this->getAssetMock());
        $collection->getFactory()->get('asset')->shouldReceive('make')->once()->with('foo/baz.js')->andReturn($assets['baz'] = $this->getAssetMock());

        $assets['bar']->shouldReceive('isRemote')->once()->andReturn(false);
        $assets['bar']->shouldReceive('getOrder')->once()->andReturn(null);
        $assets['bar']->shouldReceive('isStylesheet')->once()->andReturn(true);

        $assets['baz']->shouldReceive('isRemote')->once()->andReturn(false);
        $assets['baz']->shouldReceive('isStylesheet')->once()->andReturn(false);

        $collection->add('bar.css');
        $collection->add('baz.js');

        $this->assertEquals(array($assets['bar']), $collection->getAssets('stylesheets'));
    }


    public function testGetExcludedAssetsWithNoOrdering()
    {
        $assets = array();

        $collection = $this->getCollectionInstance();

        $collection->getFinder()->shouldReceive('withinWorkingDirectory')->twice()->andReturn(false);
        $collection->getFinder()->shouldReceive('find')->once()->with('bar.css')->andReturn('foo/bar.css');
        $collection->getFinder()->shouldReceive('find')->once()->with('http://qux.fiz/baz.js')->andReturn('http://qux.fiz/baz.js');
        $collection->getFactory()->get('asset')->shouldReceive('make')->once()->with('foo/bar.css')->andReturn($assets['bar'] = $this->getAssetMock());
        $collection->getFactory()->get('asset')->shouldReceive('make')->once()->with('http://qux.fiz/baz.js')->andReturn($assets['baz'] = $this->getAssetMock());

        $assets['bar']->shouldReceive('isRemote')->once()->andReturn(false);
        $assets['bar']->shouldReceive('getOrder')->once()->andReturn(null);
        $assets['bar']->shouldReceive('isExcluded')->once()->andReturn(true);

        $assets['baz']->shouldReceive('exclude')->once()->andReturn(true);
        $assets['baz']->shouldReceive('isRemote')->once()->andReturn(true);
        $assets['baz']->shouldReceive('getOrder')->once()->andReturn(null);
        $assets['baz']->shouldReceive('isExcluded')->once()->andReturn(true);

        $collection->add('bar.css');
        $collection->add('http://qux.fiz/baz.js');

        $this->assertEquals(array_values($assets), $collection->getExcludedAssets());
    }


    public function testGetExcludedAssetsWithOrdering()
    {
        $assets = array();

        $collection = $this->getCollectionInstance();

        $collection->getFinder()->shouldReceive('withinWorkingDirectory')->twice()->andReturn(false);
        $collection->getFinder()->shouldReceive('find')->once()->with('bar.css')->andReturn('foo/bar.css');
        $collection->getFinder()->shouldReceive('find')->once()->with('http://qux.fiz/baz.js')->andReturn('http://qux.fiz/baz.js');
        $collection->getFactory()->get('asset')->shouldReceive('make')->once()->with('foo/bar.css')->andReturn($assets['bar'] = $this->getAssetMock());
        $collection->getFactory()->get('asset')->shouldReceive('make')->once()->with('http://qux.fiz/baz.js')->andReturn($assets['baz'] = $this->getAssetMock());

        $assets['bar']->shouldReceive('isRemote')->once()->andReturn(false);
        $assets['bar']->shouldReceive('getOrder')->once()->andReturn(null);
        $assets['bar']->shouldReceive('isExcluded')->once()->andReturn(true);

        $assets['baz']->shouldReceive('exclude')->once()->andReturn(true);
        $assets['baz']->shouldReceive('isRemote')->once()->andReturn(true);
        $assets['baz']->shouldReceive('getOrder')->once()->andReturn(1);
        $assets['baz']->shouldReceive('isExcluded')->once()->andReturn(true);

        $collection->add('bar.css');
        $collection->add('http://qux.fiz/baz.js');

        $this->assertEquals(array_reverse(array_values($assets)), $collection->getExcludedAssets());
    }


    public function testGetExcludedAssetsWithSpecificGroup()
    {
        $assets = array();

        $collection = $this->getCollectionInstance();

        $collection->getFinder()->shouldReceive('withinWorkingDirectory')->twice()->andReturn(false);
        $collection->getFinder()->shouldReceive('find')->once()->with('bar.css')->andReturn('foo/bar.css');
        $collection->getFinder()->shouldReceive('find')->once()->with('http://qux.fiz/baz.js')->andReturn('http://qux.fiz/baz.js');
        $collection->getFactory()->get('asset')->shouldReceive('make')->once()->with('foo/bar.css')->andReturn($assets['bar'] = $this->getAssetMock());
        $collection->getFactory()->get('asset')->shouldReceive('make')->once()->with('http://qux.fiz/baz.js')->andReturn($assets['baz'] = $this->getAssetMock());

        $assets['bar']->shouldReceive('isRemote')->once()->andReturn(false);
        $assets['bar']->shouldReceive('isExcluded')->once()->andReturn(true);
        $assets['bar']->shouldReceive('isJavascript')->once()->andReturn(false);

        $assets['baz']->shouldReceive('exclude')->once()->andReturn(true);
        $assets['baz']->shouldReceive('isRemote')->once()->andReturn(true);
        $assets['baz']->shouldReceive('getOrder')->once()->andReturn(null);
        $assets['baz']->shouldReceive('isExcluded')->once()->andReturn(true);
        $assets['baz']->shouldReceive('isJavascript')->once()->andReturn(true);

        $collection->add('bar.css');
        $collection->add('http://qux.fiz/baz.js');

        $this->assertEquals(array($assets['baz']), $collection->getExcludedAssets('javascripts'));
    }


    public function testFiltersAreAppliedToEntireCollection()
    {
        $assets = array();

        $collection = $this->getCollectionInstance();

        $collection->getFinder()->shouldReceive('withinWorkingDirectory')->twice()->andReturn(false);
        $collection->getFinder()->shouldReceive('find')->once()->with('bar.css')->andReturn('foo/bar.css');
        $collection->getFinder()->shouldReceive('find')->once()->with('baz.css')->andReturn('foo/baz.css');
        $collection->getFactory()->get('asset')->shouldReceive('make')->once()->with('foo/bar.css')->andReturn($assets['bar'] = $this->getAssetMock());
        $collection->getFactory()->get('asset')->shouldReceive('make')->once()->with('foo/baz.css')->andReturn($assets['baz'] = $this->getAssetMock());

        $assets['bar']->shouldReceive('isRemote')->once()->andReturn(false);
        $assets['bar']->shouldReceive('getOrder')->once()->andReturn(null);

        $assets['baz']->shouldReceive('isRemote')->once()->andReturn(false);
        $assets['baz']->shouldReceive('getOrder')->once()->andReturn(null);

        $collection->getFactory()->get('filter')->shouldReceive('make')->once()->with('FooFilter')->andReturn($filter = $this->getFilterMock());

        $filter->shouldReceive('setResource')->once()->with($collection)->andReturn(m::self());
        $filter->shouldReceive('runCallback')->once()->with(null)->andReturn(m::self());
        $filter->shouldReceive('getFilter')->once()->andReturn('FooFilter');

        $assets['bar']->shouldReceive('apply')->once()->with($filter);
        $assets['baz']->shouldReceive('apply')->once()->with($filter);

        $collection->add('bar.css');
        $collection->add('baz.css');

        $this->assertInstanceOf('Basset\Filter\Filter', $collection->apply('FooFilter'));

        $collection->getAssets();
    }


    public function testDirectoryAssetsAreMergedWithCollectionAssets()
    {
        $collection = $this->getCollectionInstance();

        $collection->getFinder()->shouldReceive('setWorkingDirectory')->once()->with('foo');
        $collection->getFinder()->shouldReceive('getWorkingDirectory')->once()->andReturn('foo');
        $collection->getFinder()->shouldReceive('resetWorkingDirectory')->once();

        $collection->getFactory()->get('directory')->shouldReceive('make')->once()->with('foo')->andReturn($directory = $this->getDirectoryMock());

        $collection->directory('foo');

        $directory->shouldReceive('getAssets')->once()->andReturn(array());

        $collection->getAssets();
    }


    protected function getCollectionInstance()
    {
        $files = $this->getFilesMock();
        $finder = $this->getAssetFinderMock();
        $factory = $this->getFactoryManagerInstance();

        return new Collection('foo', $files, $finder, $factory);
    }


    protected function getAssetMock()
    {
        return m::mock('Basset\Asset');
    }


    protected function getFilesMock()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }


    protected function getAssetFinderMock()
    {
        return m::mock('Basset\AssetFinder');
    }


    protected function getAssetFactoryMock()
    {
        return m::mock('Basset\Factory\AssetFactory');
    }


    protected function getFilterFactoryMock()
    {
        return m::mock('Basset\Factory\FilterFactory');
    }


    protected function getDirectoryFactoryMock()
    {
        return m::mock('Basset\Factory\DirectoryFactory');
    }


    protected function getFilterMock()
    {
        return m::mock('Basset\Filter\Filter');
    }


    protected function getDirectoryMock()
    {
        return m::mock('Basset\Directory');
    }


    protected function getFactoryManagerInstance()
    {
        $manager = new Manager;

        $manager['asset'] = $this->getAssetFactoryMock();
        $manager['filter'] = $this->getFilterFactoryMock();
        $manager['directory'] = $this->getDirectoryFactoryMock();

        return $manager;
    }


}