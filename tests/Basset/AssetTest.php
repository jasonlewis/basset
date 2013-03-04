<?php

use Mockery as m;
use Basset\Asset;
use Basset\FilterFactory;

class AssetTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testGetAssetProperties()
    {
        $asset = $this->getAssetInstance();

        $this->assertEquals('foo/bar.css', $asset->getRelativePath());
        $this->assertEquals('path/to/foo/bar.css', $asset->getAbsolutePath());
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

        $this->assertTrue($asset->isStyle());
        $this->assertFalse($asset->isScript());
    }


    public function testAssetCanBeRemotelyHosted()
    {
        $files = $this->getFilesMock();
        $filterFactory = $this->getFilterFactoryMock();

        $asset = new Asset($files, $filterFactory, 'http://foo.com/bar.css', 'http://foo.com/bar.css', 'testing');

        $this->assertTrue($asset->isRemote());
    }


    public function testFiltersAreAppliedToAssets()
    {
        $files = $this->getFilesMock();
        $config = $this->getConfigMock();
        $config->shouldReceive('has')->once()->with('basset::filters.FooFilter')->andReturn(false);
        $filterFactory = new FilterFactory($config);

        $asset = new Asset($files, $filterFactory, 'path/to/foo/bar.css', 'foo/bar.css', 'testing');

        $asset->apply('FooFilter');

        $filter = $this->getFilterMock();
        $filter->shouldReceive('getFilter')->once()->andReturn('BarFilter');
        $asset->apply($filter);

        $filters = $asset->getFilters();

        $this->assertInstanceOf('Basset\Filter\Filter', $filters['FooFilter']);
        $this->assertInstanceOf('Basset\Filter\Filter', $filters['BarFilter']);
    }


    public function testFiltersArePrepared()
    {
        $files = $this->getFilesMock();
        $config = $this->getConfigMock();
        $filterFactory = new Basset\FilterFactory($config);

        $asset = new Asset($files, $filterFactory, 'path/to/foo/bar.css', 'foo/bar.css', 'testing');

        $fooFilter = $this->getFilterMock();
        $fooFilter->shouldReceive('getFilter')->once()->andReturn('FooFilter');
        $fooFilter->shouldReceive('getGroupRestriction')->once()->andReturn(null);
        $fooFilter->shouldReceive('getEnvironments')->once()->andReturn(array());

        $barFilter = $this->getFilterMock();
        $barFilter->shouldReceive('getFilter')->once()->andReturn('BarFilter');
        $barFilter->shouldReceive('getGroupRestriction')->once()->andReturn('script');
        $barFilter->shouldReceive('getEnvironments')->once()->andReturn(array());

        $bazFilter = $this->getFilterMock();
        $bazFilter->shouldReceive('getFilter')->once()->andReturn('BazFilter');
        $bazFilter->shouldReceive('getGroupRestriction')->once()->andReturn(null);
        $bazFilter->shouldReceive('getEnvironments')->once()->andReturn(array('production'));

        $asset->apply($fooFilter);
        $asset->apply($barFilter);
        $asset->apply($bazFilter);

        $asset->prepareFilters();

        $filters = $asset->getFilters();

        $this->assertArrayHasKey('FooFilter', $filters);
        $this->assertArrayNotHasKey('BarFilter', $filters);
        $this->assertArrayNotHasKey('BazFilter', $filters);
    }


    public function testAssetIsBuiltCorrectly()
    {
        $contents = 'html { background-color: #fff; }';

        $files = $this->getFilesMock();
        $files->shouldReceive('getRemote')->once()->andReturn($contents);
        $config = $this->getConfigMock();
        $filterFactory = new FilterFactory($config);

        $asset = new Asset($files, $filterFactory, 'path/to/foo/bar.css', 'foo/bar.css', 'testing');

        $instantiatedFilter = m::mock('Assetic\Filter\FilterInterface');
        $instantiatedFilter->shouldReceive('filterLoad')->once()->andReturn(null);
        $instantiatedFilter->shouldReceive('filterDump')->once()->andReturnUsing(function($asset) use ($contents)
        {
            $asset->setContent(str_replace('html', 'body', $contents));
        });

        $filter = $this->getFilterMock();
        $filter->shouldReceive('getFilter')->once()->andReturn('BodyFilter');
        $filter->shouldReceive('getGroupRestriction')->once()->andReturn(null);
        $filter->shouldReceive('getEnvironments')->once()->andReturn(array());
        $filter->shouldReceive('instantiate')->once()->andReturn($instantiatedFilter);

        $asset->apply($filter);

        $this->assertEquals('body { background-color: #fff; }', $asset->build());
    }


    protected function getAssetInstance()
    {
        $files = $this->getFilesMock();
        $filterFactory = $this->getFilterFactoryMock();

        return new Asset($files, $filterFactory, 'path/to/foo/bar.css', 'foo/bar.css', 'testing');
    }


    protected function getFilesMock()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }


    protected function getFilterFactoryMock()
    {
        return m::mock('Basset\FilterFactory');
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