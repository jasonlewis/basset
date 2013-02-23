<?php

use Mockery as m;

class AssetTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testCanGetAssetProperties()
    {
        $asset = $this->getAssetInstance();
        $this->assertEquals('foo/bar.css', $asset->getRelativePath());
        $this->assertEquals('/absolute/foo/bar.css', $asset->getAbsolutePath());
    }


    public function testAssetsCanBeIgnored()
    {
        $asset = $this->getAssetInstance();
        $asset->ignore();
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
        $asset = new JasonLewis\Basset\Asset($files, 'http://foo.com/bar.css', 'http://foo.com/bar.css', 'local');
        $this->assertTrue($asset->isRemote());
    }


    public function testFiltersAreAppliedToAssets()
    {
        $asset = $this->getAssetInstance();
        $asset->apply('RandomFilter');
        $filter = m::mock('JasonLewis\Basset\Filter');
        $filter->shouldReceive('getFilter')->once()->andReturn('ExistingFilter');
        $asset->apply($filter);
        $filters = $asset->getFilters();
        $this->assertArrayHasKey('RandomFilter', $filters);
        $this->assertArrayHasKey('ExistingFilter', $filters);
        $this->assertInstanceOf('JasonLewis\Basset\Filter', $filters['RandomFilter']);
        $this->assertInstanceOf('JasonLewis\Basset\Filter', $filters['ExistingFilter']);
    }


    public function testFiltersAreCorrectlyPrepared()
    {
        $asset = $this->getAssetInstance();
        $fooFilter = m::mock('JasonLewis\Basset\Filter');
        $fooFilter->shouldReceive('getFilter')->once()->andReturn('FooFilter');
        $fooFilter->shouldReceive('getGroupRestriction')->once()->andReturn(null);
        $fooFilter->shouldReceive('getEnvironments')->once()->andReturn(array());
        $barFilter = m::mock('JasonLewis\Basset\Filter');
        $barFilter->shouldReceive('getFilter')->once()->andReturn('BarFilter');
        $barFilter->shouldReceive('getGroupRestriction')->once()->andReturn('script');
        $barFilter->shouldReceive('getEnvironments')->once()->andReturn(array());
        $zooFilter = m::mock('JasonLewis\Basset\Filter');
        $zooFilter->shouldReceive('getFilter')->once()->andReturn('ZooFilter');
        $zooFilter->shouldReceive('getGroupRestriction')->once()->andReturn(null);
        $zooFilter->shouldReceive('getEnvironments')->once()->andReturn(array('production'));
        $asset->apply($fooFilter);
        $asset->apply($barFilter);
        $asset->apply($zooFilter);
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
        $asset = new JasonLewis\Basset\Asset($files, '/absolute/foo/bar.css', 'foo/bar.css', 'local');
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
        return new JasonLewis\Basset\Asset($files, '/absolute/foo/bar.css', 'foo/bar.css', 'local');
    }

}