<?php

use Mockery as m;
use Basset\Asset;
use Basset\Factory\FilterFactory;

class AssetTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function setUp()
    {
        $this->files = m::mock('Illuminate\Filesystem\Filesystem');
        $this->factory = m::mock('Basset\Factory\Manager');

        $this->files->shouldReceive('lastModified')->with('path/to/public/foo/bar.sass')->andReturn('1368422603');

        $this->asset = new Asset($this->files, $this->factory, 'path/to/public/foo/bar.sass', 'foo/bar.sass', 'testing', 1);
    }


    public function testGetAssetProperties()
    {
        $this->assertEquals('foo/bar.sass', $this->asset->getRelativePath());
        $this->assertEquals('path/to/public/foo/bar.sass', $this->asset->getAbsolutePath());
        $this->assertEquals('foo/bar.css', $this->asset->getUsablePath());
        $this->assertEquals('foo/bar-2a4bdbebcbf798cb0b59078d98136e3d.css', $this->asset->getFingerprintedPath());
        $this->assertEquals('css', $this->asset->getUsableExtension());
        $this->assertInstanceOf('Illuminate\Support\Collection', $this->asset->getFilters());
        $this->assertEquals('stylesheets', $this->asset->getGroup());
    }


    public function testAssetsCanBeExcluded()
    {
        $this->assertTrue($this->asset->exclude()->isExcluded());
    }


    public function testCheckingOfAssetGroup()
    {
        $this->assertTrue($this->asset->isStylesheet());
        $this->assertFalse($this->asset->isJavascript());
    }


    public function testAssetCanBeRemotelyHosted()
    {
        $asset = new Asset($this->files, $this->factory, 'http://foo.com/bar.css', 'http://foo.com/bar.css', 'testing', 1);

        $this->assertTrue($asset->isRemote());
    }


    public function testAssetCanBeRemotelyHostedWithRelativeProtocol()
    {
        $asset = new Asset($this->files, $this->factory, '//foo.com/bar.css', '//foo.com/bar.css', 'testing', 1);

        $this->assertTrue($asset->isRemote());
    }


    public function testSettingCustomOrderOfAsset()
    {
        $this->asset->first();
        $this->assertEquals(1, $this->asset->getOrder());

        $this->asset->second();
        $this->assertEquals(2, $this->asset->getOrder());

        $this->asset->third();
        $this->assertEquals(3, $this->asset->getOrder());

        $this->asset->order(10);
        $this->assertEquals(10, $this->asset->getOrder());
    }


    public function testFiltersAreAppliedToAssets()
    {
        $this->factory->shouldReceive('offsetGet')->once()->with('filter')->andReturn($filterFactory = m::mock('Basset\Factory\FilterFactory'));

        $filterFactory->shouldReceive('make')->once()->with('FooFilter')->andReturn($filter = m::mock('Basset\Filter\Filter'));
        
        $filter->shouldReceive('setResource')->once()->with($this->asset)->andReturn(m::self());
        $filter->shouldReceive('getFilter')->once()->andReturn('FooFilter');

        $this->asset->apply('FooFilter');

        $filters = $this->asset->getFilters();
        
        $this->assertArrayHasKey('FooFilter', $filters->all());
        $this->assertInstanceOf('Basset\Filter\Filter', $filters['FooFilter']);
    }


    public function testFiltersArePreparedCorrectly()
    {
        $fooFilter = m::mock('Basset\Filter\Filter')->shouldDeferMissing();
        $fooFilterInstance = m::mock('stdClass, Assetic\Filter\FilterInterface');
        $fooFilterInstance->shouldReceive('filterLoad')->once();
        $fooFilterInstance->shouldReceive('filterDump')->once();
        $fooFilter->shouldReceive('getFilter')->once()->andReturn('FooFilter');
        $fooFilter->shouldReceive('getClassName')->once()->andReturn($fooFilterInstance);

        $barFilter = m::mock('Basset\Filter\Filter')->shouldDeferMissing();
        $barFilter->shouldReceive('getFilter')->once()->andReturn('BarFilter');
        $barFilter->shouldReceive('getClassName')->once()->andReturn(m::mock('stdClass, Assetic\Filter\FilterInterface'));

        $bazFilter = m::mock('Basset\Filter\Filter')->shouldDeferMissing();
        $bazFilter->shouldReceive('getFilter')->once()->andReturn('BazFilter');
        $bazFilter->shouldReceive('getClassName')->once()->andReturn(m::mock('stdClass, Assetic\Filter\FilterInterface'));

        $quxFilter = m::mock('Basset\Filter\Filter')->shouldDeferMissing();
        $quxFilter->shouldReceive('getFilter')->once()->andReturn('QuxFilter');
        $quxFilter->shouldReceive('getClassName')->once()->andReturn(m::mock('stdClass, Assetic\Filter\FilterInterface'));

        $vanFilter = m::mock('Basset\Filter\Filter')->shouldDeferMissing();
        $vanFilterInstance = m::mock('stdClass, Assetic\Filter\FilterInterface');
        $vanFilterInstance->shouldReceive('filterLoad')->once();
        $vanFilterInstance->shouldReceive('filterDump')->once();
        $vanFilter->shouldReceive('getFilter')->once()->andReturn('VanFilter');
        $vanFilter->shouldReceive('getClassName')->once()->andReturn($vanFilterInstance);

        $this->files->shouldReceive('getRemote')->once()->with('path/to/public/foo/bar.sass')->andReturn('');
        $this->factory->shouldReceive('offsetGet')->times(5)->with('filter')->andReturn(new FilterFactory(m::mock('Illuminate\Config\Repository')));

        $this->asset->apply($fooFilter);
        $this->asset->apply($barFilter)->whenAssetIsJavascript();
        $this->asset->apply($bazFilter)->whenEnvironmentIs('production');
        $this->asset->apply($quxFilter)->whenAssetIs('*.js');
        $this->asset->apply($vanFilter)->whenAssetIs('*.sass');

        $this->asset->build();

        $filters = $this->asset->getFilters()->all();

        $this->assertArrayHasKey('FooFilter', $filters);
        $this->assertArrayHasKey('VanFilter', $filters);
        $this->assertArrayNotHasKey('BarFilter', $filters);
        $this->assertArrayNotHasKey('BazFilter', $filters);
        $this->assertArrayNotHasKey('QuxFilter', $filters);
    }


    public function testAssetIsBuiltCorrectly()
    {
        $contents = 'html { background-color: #fff; }';

        $instantiatedFilter = m::mock('Assetic\Filter\FilterInterface');
        $instantiatedFilter->shouldReceive('filterLoad')->once()->andReturn(null);
        $instantiatedFilter->shouldReceive('filterDump')->once()->andReturnUsing(function($asset) use ($contents)
        {
            $asset->setContent(str_replace('html', 'body', $contents));
        });

        $filter = m::mock('Basset\Filter\Filter')->shouldDeferMissing();
        $filter->shouldReceive('setResource')->once()->with($this->asset)->andReturn(m::self());
        $filter->shouldReceive('getFilter')->once()->andReturn('BodyFilter');
        $filter->shouldReceive('getInstance')->once()->andReturn($instantiatedFilter);


        $config = m::mock('Illuminate\Config\Repository');

        $this->files->shouldReceive('getRemote')->once()->with('path/to/public/foo/bar.sass')->andReturn($contents);
        $this->factory->shouldReceive('offsetGet')->once()->with('filter')->andReturn(new FilterFactory($config));

        $this->asset->apply($filter);

        $this->assertEquals('body { background-color: #fff; }', $this->asset->build());
    }


}