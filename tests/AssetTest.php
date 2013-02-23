<?php

use Mockery as m;

class AssetTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testGetAssetProperties()
    {
        $asset = $this->getAssetInstance();
        $this->assertEquals('foo/bar.css', $asset->getRelativePath());
        $this->assertEquals('/absolute/foo/bar.css', $asset->getAbsolutePath());
        $this->assertEquals('css', $asset->getValidExtension());
    }


    public function testAssetsCanBeIgnored()
    {
        $asset = $this->getAssetInstance()->ignore();
        $this->assertTrue($asset->isIgnored());
    }


    public function testAssetIsStyleOrScript()
    {
        $asset = $this->getAssetInstance();
        $this->assertTrue($asset->isStyle());
        $this->assertFalse($asset->isScript());
    }


    public function testAssetCanBeRemotelyHosted()
    {
        $files = m::mock('Illuminate\Filesystem\Filesystem');
        $asset = new JasonLewis\Basset\Asset($files, m::mock('JasonLewis\Basset\FilterFactory'), 'http://foo.com/bar.css', 'http://foo.com/bar.css', 'local');
        $this->assertTrue($asset->isRemote());
    }


    public function testFiltersAreAppliedToAssets()
    {
        $files = m::mock('Illuminate\Filesystem\Filesystem');
        $config = m::mock('Illuminate\Config\Repository');
        $config->shouldReceive('has')->once()->with('basset::filters.RandomFilter')->andReturn(false);
        $filterFactory = new JasonLewis\Basset\FilterFactory($config);
        $asset = new JasonLewis\Basset\Asset($files, $filterFactory, '/absolute/foo/bar.css', 'foo/bar.css', 'local');
        $asset->apply('RandomFilter');
        $filter = m::mock('JasonLewis\Basset\Filter');
        $filter->shouldReceive('getFilter')->once()->andReturn('ExistingFilter');
        $asset->apply($filter);
        $filters = $asset->getFilters();
        $this->assertInstanceOf('JasonLewis\Basset\Filter', $filters['RandomFilter']);
        $this->assertInstanceOf('JasonLewis\Basset\Filter', $filters['ExistingFilter']);
    }


    public function testFiltersAreCorrectlyPrepared()
    {
        $files = m::mock('Illuminate\Filesystem\Filesystem');
        $config = m::mock('Illuminate\Config\Repository');
        $filterFactory = new JasonLewis\Basset\FilterFactory($config);
        $asset = new JasonLewis\Basset\Asset($files, $filterFactory, '/absolute/foo/bar.css', 'foo/bar.css', 'local');
        $fooFilter = m::mock('JasonLewis\Basset\Filter');
        $fooFilter->shouldReceive('getFilter')->once()->andReturn('FooFilter');
        $fooFilter->shouldReceive('getGroupRestriction')->once()->andReturn(null);
        $fooFilter->shouldReceive('getEnvironments')->once()->andReturn(array());
        $barFilter = m::mock('JasonLewis\Basset\Filter');
        $barFilter->shouldReceive('getFilter')->once()->andReturn('BarFilter');
        $barFilter->shouldReceive('getGroupRestriction')->once()->andReturn('script');
        $barFilter->shouldReceive('getEnvironments')->once()->andReturn(array());
        $bazFilter = m::mock('JasonLewis\Basset\Filter');
        $bazFilter->shouldReceive('getFilter')->once()->andReturn('BazFilter');
        $bazFilter->shouldReceive('getGroupRestriction')->once()->andReturn(null);
        $bazFilter->shouldReceive('getEnvironments')->once()->andReturn(array('production'));
        $asset->apply($fooFilter);
        $asset->apply($barFilter);
        $asset->apply($bazFilter);
        $asset->prepareFilters();
        $filters = $asset->getFilters();
        $this->assertArrayHasKey('FooFilter', $filters);
        $this->assertCount(1, $filters);
    }


    public function testAssetIsCompiledCorrectly()
    {
        $contents = 'html { background-color: #fff; }';
        $files = m::mock('Illuminate\Filesystem\Filesystem');
        $files->shouldReceive('getRemote')->once()->andReturn($contents);
        $config = m::mock('Illuminate\Config\Repository');
        $filterFactory = new JasonLewis\Basset\FilterFactory($config);
        $asset = new JasonLewis\Basset\Asset($files, $filterFactory, '/absolute/foo/bar.css', 'foo/bar.css', 'local');
        $instantiatedFilter = m::mock('Assetic\Filter\FilterInterface');
        $instantiatedFilter->shouldReceive('filterLoad')->once()->andReturn(null);
        $instantiatedFilter->shouldReceive('filterDump')->once()->andReturnUsing(function($asset) use ($contents)
        {
            $asset->setContent(str_replace('html', 'body', $contents));
        });
        $filter = m::mock('JasonLewis\Basset\Filter');
        $filter->shouldReceive('getFilter')->once()->andReturn('BodyFilter');
        $filter->shouldReceive('getGroupRestriction')->once()->andReturn(null);
        $filter->shouldReceive('getEnvironments')->once()->andReturn(array());
        $filter->shouldReceive('instantiate')->once()->andReturn($instantiatedFilter);
        $asset->apply($filter);
        $this->assertEquals('body { background-color: #fff; }', $asset->compile());
    }


    protected function getAssetInstance()
    {
        $files = m::mock('Illuminate\Filesystem\Filesystem');
        $filterFactory = m::mock('JasonLewis\Basset\FilterFactory');
        return new JasonLewis\Basset\Asset($files, $filterFactory, '/absolute/foo/bar.css', 'foo/bar.css', 'local');
    }

}