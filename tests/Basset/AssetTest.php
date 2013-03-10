<?php

use Mockery as m;
use Basset\Asset;
use Basset\Factory\FilterFactory;

class AssetTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testGetAssetProperties()
    {
        $asset = $this->getAssetInstance();

        $this->assertEquals('foo/bar.sass', $asset->getRelativePath());
        $this->assertEquals('path/to/foo/bar.sass', $asset->getAbsolutePath());
        $this->assertEquals('foo/bar.css', $asset->getUsablePath());
        $this->assertEquals('css', $asset->getUsableExtension());
        $this->assertEquals(array(), $asset->getFilters());
        $this->assertEquals('styles', $asset->getGroup());
    }


    public function testAssetsCanBeExcluded()
    {
        $asset = $this->getAssetInstance();

        $this->assertTrue($asset->exclude()->isExcluded());
    }


    public function testCheckingOfAssetGroup()
    {
        $asset = $this->getAssetInstance();

        $this->assertTrue($asset->isStylesheet());
        $this->assertFalse($asset->isJavascript());
    }


    public function testAssetCanBeRemotelyHosted()
    {
        $files = $this->getFilesMock();
        $factory = $this->getFactoryManagerMock();

        $asset = new Asset($files, $factory, 'http://foo.com/bar.css', 'http://foo.com/bar.css', 'testing');

        $this->assertTrue($asset->isRemote());
    }


    public function testSettingOfAssetsOrder()
    {
        $asset = $this->getAssetInstance();

        $asset->first();
        $this->assertEquals(1, $asset->getOrder());

        $asset->second();
        $this->assertEquals(2, $asset->getOrder());

        $asset->third();
        $this->assertEquals(3, $asset->getOrder());

        $asset->order(10);
        $this->assertEquals(10, $asset->getOrder());
    }


    public function testFiltersAreAppliedToAssets()
    {
        $asset = $this->getAssetInstance();
        $asset->getFactory()->shouldReceive('offsetGet')->once()->with('filter')->andReturn($filterFactory = $this->getFilterFactoryMock());

        $filterFactory->shouldReceive('make')->once()->with('FooFilter')->andReturn($filter = $this->getFilterMock());
        
        $filter->shouldReceive('setResource')->once()->with($asset)->andReturn(m::self());
        $filter->shouldReceive('fireCallback')->once()->with(null)->andReturn(m::self());
        $filter->shouldReceive('getFilter')->once()->andReturn('FooFilter');

        $asset->apply('FooFilter');

        $filters = $asset->getFilters();
        
        $this->assertArrayHasKey('FooFilter', $filters);
        $this->assertInstanceOf('Basset\Filter\Filter', $filters['FooFilter']);
    }


    public function testFiltersArePreparedCorrectly()
    {
        $asset = $this->getAssetInstance();

        $fooFilter = $this->getFilterMock();
        $fooFilter->shouldReceive('setResource')->once()->with($asset)->andReturn(m::self());
        $fooFilter->shouldReceive('fireCallback')->once()->with(null)->andReturn(m::self());
        $fooFilter->shouldReceive('getInstance')->once();
        $fooFilter->shouldReceive('getFilter')->once()->andReturn('FooFilter');
        $fooFilter->shouldReceive('getGroupRestriction')->once()->andReturn(null);
        $fooFilter->shouldReceive('getEnvironments')->once()->andReturn(array());

        $barFilter = $this->getFilterMock();
        $barFilter->shouldReceive('setResource')->once()->with($asset)->andReturn(m::self());
        $barFilter->shouldReceive('fireCallback')->once()->with(null)->andReturn(m::self());
        $barFilter->shouldReceive('getFilter')->once()->andReturn('BarFilter');
        $barFilter->shouldReceive('getGroupRestriction')->once()->andReturn('javascript');
        $barFilter->shouldReceive('getEnvironments')->once()->andReturn(array());

        $bazFilter = $this->getFilterMock();
        $bazFilter->shouldReceive('setResource')->once()->with($asset)->andReturn(m::self());
        $bazFilter->shouldReceive('fireCallback')->once()->with(null)->andReturn(m::self());
        $bazFilter->shouldReceive('getFilter')->once()->andReturn('BazFilter');
        $bazFilter->shouldReceive('getGroupRestriction')->once()->andReturn(null);
        $bazFilter->shouldReceive('getEnvironments')->once()->andReturn(array('production'));

        $config = $this->getConfigMock();

        $asset->getFiles()->shouldReceive('getRemote')->once()->with('path/to/foo/bar.sass')->andReturn('');
        $asset->getFactory()->shouldReceive('offsetGet')->times(3)->with('filter')->andReturn(new FilterFactory($config));

        $asset->apply($fooFilter);
        $asset->apply($barFilter);
        $asset->apply($bazFilter);

        $asset->build();

        $filters = $asset->getFilters();

        $this->assertArrayHasKey('FooFilter', $filters);
        $this->assertArrayNotHasKey('BarFilter', $filters);
        $this->assertArrayNotHasKey('BazFilter', $filters);
    }


    public function testAssetIsBuiltCorrectly()
    {
        $contents = 'html { background-color: #fff; }';

        $asset = $this->getAssetInstance();

        $instantiatedFilter = m::mock('Assetic\Filter\FilterInterface');
        $instantiatedFilter->shouldReceive('filterLoad')->once()->andReturn(null);
        $instantiatedFilter->shouldReceive('filterDump')->once()->andReturnUsing(function($asset) use ($contents)
        {
            $asset->setContent(str_replace('html', 'body', $contents));
        });

        $filter = $this->getFilterMock();
        $filter->shouldReceive('setResource')->once()->with($asset)->andReturn(m::self());
        $filter->shouldReceive('fireCallback')->once()->with(null)->andReturn(m::self());
        $filter->shouldReceive('getFilter')->once()->andReturn('BodyFilter');
        $filter->shouldReceive('getGroupRestriction')->once()->andReturn(null);
        $filter->shouldReceive('getEnvironments')->once()->andReturn(array());
        $filter->shouldReceive('getInstance')->once()->andReturn($instantiatedFilter);


        $config = $this->getConfigMock();

        $asset->getFiles()->shouldReceive('getRemote')->once()->with('path/to/foo/bar.sass')->andReturn($contents);
        $asset->getFactory()->shouldReceive('offsetGet')->once()->with('filter')->andReturn(new FilterFactory($config));

        $asset->apply($filter);

        $this->assertEquals('body { background-color: #fff; }', $asset->build());
    }


    protected function getAssetInstance()
    {
        $files = $this->getFilesMock();
        $factory = $this->getFactoryManagerMock();

        return new Asset($files, $factory, 'path/to/foo/bar.sass', 'foo/bar.sass', 'testing');
    }


    protected function getFilesMock()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }


    protected function getFactoryManagerMock()
    {
        return m::mock('Basset\Factory\Manager');
    }


    protected function getFilterFactoryMock()
    {
        return m::mock('Basset\Factory\FilterFactory');
    }


    protected function getFilterMock()
    {
        return m::mock('Basset\Filter\Filter');
    }


    protected function getConfigMock()
    {
        return m::mock('Illuminate\Config\Repository');
    }


}