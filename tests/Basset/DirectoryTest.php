<?php

use Mockery as m;
use Basset\Factory\Manager;
use Basset\Factory\FilterFactory;

class DirectoryTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testRequireDirectory()
    {
        $directory = $this->getDirectoryPartialMock();

        $files = array(
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo')
        );
        $files[0]->shouldReceive('isFile')->once()->andReturn(true);
        $files[0]->shouldReceive('getPathname')->once()->andReturn('baz/foo.css');

        $files[1]->shouldReceive('isFile')->once()->andReturn(false);

        $files[2]->shouldReceive('isFile')->once()->andReturn(true);
        $files[2]->shouldReceive('getPathname')->once()->andReturn('baz/bar.css');

        $directory->shouldReceive('iterateDirectory')->once()->with('baz')->andReturn($files);

        $assets = array();

        $directory->getFactory()->get('asset')->shouldReceive('make')->once()->with('baz/foo.css')->andReturn($assets[] = $this->getAssetMock());
        $directory->getFactory()->get('asset')->shouldReceive('make')->once()->with('baz/bar.css')->andReturn($assets[] = $this->getAssetMock());

        $directory->requireDirectory();

        $this->assertEquals($assets, $directory->getAssets());
    }


    public function testRequireTree()
    {
        $directory = $this->getDirectoryPartialMock();

        $files = array(
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo')
        );
        $files[0]->shouldReceive('isFile')->once()->andReturn(true);
        $files[0]->shouldReceive('getPathname')->once()->andReturn('baz/foo.css');

        $files[1]->shouldReceive('isFile')->once()->andReturn(true);
        $files[1]->shouldReceive('getPathname')->once()->andReturn('baz/qux/bar.css');

        $directory->shouldReceive('recursivelyIterateDirectory')->once()->with('baz')->andReturn($files);

        $assets = array();

        $directory->getFactory()->get('asset')->shouldReceive('make')->once()->with('baz/foo.css')->andReturn($assets[] = $this->getAssetMock());
        $directory->getFactory()->get('asset')->shouldReceive('make')->once()->with('baz/qux/bar.css')->andReturn($assets[] = $this->getAssetMock());

        $directory->requireTree();

        $this->assertEquals($assets, $directory->getAssets());
    }


    public function testExcludingOfFilesFromDirectory()
    {
        $directory = $this->getDirectoryPartialMock();

        $files = array(
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo')
        );
        $files[0]->shouldReceive('isFile')->once()->andReturn(true);
        $files[0]->shouldReceive('getPathname')->once()->andReturn('baz/foo.css');

        $files[1]->shouldReceive('isFile')->once()->andReturn(true);
        $files[1]->shouldReceive('getPathname')->once()->andReturn('baz/bar.css');

        $directory->shouldReceive('iterateDirectory')->once()->with('baz')->andReturn($files);

        $assets = array();

        $directory->getFactory()->get('asset')->shouldReceive('make')->once()->with('baz/foo.css')->andReturn($assets[] = $this->getAssetMock());
        $directory->getFactory()->get('asset')->shouldReceive('make')->once()->with('baz/bar.css')->andReturn($assets[] = $this->getAssetMock());

        $assets[0]->shouldReceive('getRelativePath')->once()->andReturn('baz/foo.css');
        $assets[1]->shouldReceive('getRelativePath')->once()->andReturn('baz/bar.css');

        $directory->requireDirectory()->except('baz/foo.css');

        $this->assertEquals(array(end($assets)), $directory->getAssets());
    }


    public function testIncludingCertainFilesFromDirectory()
    {
        $directory = $this->getDirectoryPartialMock();

        $files = array(
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo')
        );
        $files[0]->shouldReceive('isFile')->once()->andReturn(true);
        $files[0]->shouldReceive('getPathname')->once()->andReturn('baz/foo.css');

        $files[1]->shouldReceive('isFile')->once()->andReturn(true);
        $files[1]->shouldReceive('getPathname')->once()->andReturn('baz/bar.css');

        $directory->shouldReceive('iterateDirectory')->once()->with('baz')->andReturn($files);

        $assets = array();

        $directory->getFactory()->get('asset')->shouldReceive('make')->once()->with('baz/foo.css')->andReturn($assets[] = $this->getAssetMock());
        $directory->getFactory()->get('asset')->shouldReceive('make')->once()->with('baz/bar.css')->andReturn($assets[] = $this->getAssetMock());

        $assets[0]->shouldReceive('getRelativePath')->once()->andReturn('baz/foo.css');
        $assets[1]->shouldReceive('getRelativePath')->once()->andReturn('baz/bar.css');

        $directory->requireDirectory()->only('baz/foo.css');

        $this->assertEquals(array(reset($assets)), $directory->getAssets());
    }


    public function testFiltersAreAppliedToEntireDirectory()
    {
        $directory = $this->getDirectoryPartialMock();

        $files = array(
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo')
        );
        $files[0]->shouldReceive('isFile')->once()->andReturn(true);
        $files[0]->shouldReceive('getPathname')->once()->andReturn('baz/foo.css');

        $files[1]->shouldReceive('isFile')->once()->andReturn(true);
        $files[1]->shouldReceive('getPathname')->once()->andReturn('baz/bar.css');

        $directory->shouldReceive('iterateDirectory')->once()->with('baz')->andReturn($files);

        $assets = array();

        $directory->getFactory()->get('asset')->shouldReceive('make')->once()->with('baz/foo.css')->andReturn($assets[] = $this->getAssetMock());
        $directory->getFactory()->get('asset')->shouldReceive('make')->once()->with('baz/bar.css')->andReturn($assets[] = $this->getAssetMock());

        $directory->getFactory()->get('filter')->shouldReceive('make')->once()->with('FooFilter')->andReturn($filter = $this->getFilterMock());
        $filter->shouldReceive('setResource')->once()->with($directory)->andReturn(m::self());
        $filter->shouldReceive('fireCallback')->once()->with(null)->andReturn(m::self());
        $filter->shouldReceive('getFilter')->once()->andReturn('FooFilter');

        $assets[0]->shouldReceive('apply')->once()->with($filter);
        $assets[1]->shouldReceive('apply')->once()->with($filter);

        $this->assertInstanceOf('Basset\Filter\Filter', $directory->requireDirectory()->apply('FooFilter'));

        $directory->getAssets();
    }


    public function testFiltersAreAppliedToEntireDirectoryAndCallbackIsFired()
    {
        $directory = $this->getDirectoryPartialMock();
        $directory->getFactory()->get('filter')->shouldReceive('make')->once()->with('FooFilter')->andReturn($filter = $this->getFilterMock());

        $callback = function()
        {
            return 'bar';
        };

        $filter->shouldReceive('setResource')->once()->with($directory)->andReturn(m::self());
        $filter->shouldReceive('fireCallback')->once()->with($callback)->andReturn(m::self());
        $filter->shouldReceive('getFilter')->once()->andReturn('FooFilter');

        $this->assertInstanceOf('Basset\Filter\Filter', $directory->apply('FooFilter', $callback));
    }


    protected function getDirectoryPartialMock()
    {
        $files = $this->getFilesMock();
        $factory = $this->getFactoryManagerInstance();

        return m::mock('Basset\Directory', array('baz', $files, $factory))->shouldDeferMissing();
    }


    protected function getFilesMock()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }


    protected function getAssetFactoryMock()
    {
        return m::mock('Basset\Factory\AssetFactory');
    }


    protected function getFilterFactoryMock()
    {
        return m::mock('Basset\Factory\FilterFactory');
    }


    protected function getAssetMock()
    {
        return m::mock('Basset\Asset');
    }


    protected function getFilterMock()
    {
        return m::mock('Basset\Filter\Filter');
    }


    protected function getFactoryManagerInstance()
    {
        $factory = new Manager;

        $factory['asset'] = $this->getAssetFactoryMock();
        $factory['filter'] = $this->getFilterFactoryMock();

        return $factory;
    }


}