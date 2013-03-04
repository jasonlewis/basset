<?php

use Mockery as m;
use Basset\Collection;
use Basset\AssetFactory;
use Basset\FilterFactory;
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


    public function testAddAssetFromPublicDirectory()
    {
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->twice()->with('path/to/public/bar.css')->andReturn(true);
        $config = $this->getConfigMock();
        $config->shouldReceive('has')->once()->with('basset::assets.bar.css')->andReturn(false);
        $filterFactory = $this->getFilterFactoryMock();
        $assetFactory = new AssetFactory($files, $filterFactory, 'path/to/public', 'testing');

        $collection = new Collection('foo', $files, $config, $assetFactory, $filterFactory);

        $collection->add('bar.css');

        $assets = $collection->getAssets();

        $this->assertInstanceOf('Basset\Asset', array_pop($assets));
    }


    public function testAddAssetFromAlias()
    {
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->twice()->with('path/to/public/bar.css')->andReturn(true);
        $config = $this->getConfigMock();
        $config->shouldReceive('has')->once()->with('basset::assets.foo')->andReturn(true);
        $config->shouldReceive('get')->once()->with('basset::assets.foo')->andReturn('bar.css');
        $filterFactory = $this->getFilterFactoryMock();
        $assetFactory = new AssetFactory($files, $filterFactory, 'path/to/public', 'testing');

        $collection = new Collection('foo', $files, $config, $assetFactory, $filterFactory);

        $collection->add('foo');

        $assets = $collection->getAssets();

        $this->assertInstanceOf('Basset\Asset', array_pop($assets));
    }


    public function testAddAssetFromRemoteLocation()
    {
        $files = $this->getFilesMock();
        $config = $this->getConfigMock();
        $config->shouldReceive('has')->once()->with('basset::assets.http://foo.com/bar.css')->andReturn(false);
        $filterFactory = $this->getFilterFactoryMock();
        $assetFactory = new AssetFactory($files, $filterFactory, 'path/to/public', 'testing');

        $collection = new Collection('foo', $files, $config, $assetFactory, $filterFactory);

        $collection->add('http://foo.com/bar.css');

        $assets = $collection->getAssets();
        $asset = array_pop($assets);

        $this->assertTrue($asset->isRemote());
    }


    public function testRemoteAssetIsExcludedByDefault()
    {
        $files = $this->getFilesMock();
        $config = $this->getConfigMock();
        $config->shouldReceive('has')->once()->with('basset::assets.http://foo.com/bar.css')->andReturn(false);
        $filterFactory = $this->getFilterFactoryMock();
        $assetFactory = new AssetFactory($files, $filterFactory, 'path/to/public', 'testing');

        $collection = new Collection('foo', $files, $config, $assetFactory, $filterFactory);

        $collection->add('http://foo.com/bar.css');

        $assets = $collection->getAssets();
        $asset = array_pop($assets);

        $this->assertTrue($asset->isExcluded());
    }


    public function testAddAssetsFromDefinedDirectory()
    {
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->once()->with('path/to/public/bar.css')->andReturn(false);
        $files->shouldReceive('exists')->once()->with('path/to/public/nested/bar.css')->andReturn(true);
        $config = $this->getConfigMock();
        $config->shouldReceive('has')->once()->with('basset::assets.bar.css')->andReturn(false);
        $config->shouldReceive('get')->once()->with('basset::directories')->andReturn(array('nested' => 'path/to/public/nested'));
        $filterFactory = $this->getFilterFactoryMock();
        $file = m::mock('SplFileInfo');
        $file->shouldReceive('getRealPath')->once()->andReturn('path/to/public/nested/bar.css');

        $assetFactory = new AssetFactory($files, $filterFactory, 'path/to/public', 'testing');

        $directory = m::mock('Basset\Directory[recursivelyIterateDirectory]', array('path/to/public/nested', $files, $assetFactory, $filterFactory));
        $directory->shouldReceive('recursivelyIterateDirectory')->once()->with('path/to/public/nested')->andReturn(array($file));

        $collection = m::mock('Basset\Collection[parseDirectoryPath]', array('foo', $files, $config, $assetFactory, $filterFactory));
        $collection->shouldReceive('parseDirectoryPath')->with('path/to/public/nested')->andReturn($directory);

        $collection->add('bar.css');

        $assets = $collection->getAssets();

        $this->assertInstanceOf('Basset\Asset', array_pop($assets));
    }


    public function testAddAssetFromWithinWorkingDirectory()
    {
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->twice()->with('path/to/public/nested/bar.css')->andReturn(true);
        $config = $this->getConfigMock();
        $config->shouldReceive('has')->once()->with('basset::assets.bar.css')->andReturn(false);
        $filterFactory = $this->getFilterFactoryMock();
        $directory = $this->getDirectoryMock();
        $directory->shouldReceive('getPath')->twice()->andReturn('path/to/public/nested');

        $assetFactory = new AssetFactory($files, $filterFactory, 'path/to/public', 'testing');

        $collection = m::mock('Basset\Collection[parseDirectoryPath]', array('foo', $files, $config, $assetFactory, $filterFactory));
        $collection->shouldReceive('parseDirectoryPath')->with('nested')->andReturn($directory);

        $collection->directory('nested', function($collection)
        {
            $collection->add('bar.css');
        });

        $assets = $collection->getAssets();

        $this->assertInstanceOf('Basset\Asset', array_pop($assets));
    }


    public function testBlankAssetInstanceReturnedForNonExistentAssets()
    {
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->once()->with('path/to/public/bar.css')->andReturn(false);
        $config = $this->getConfigMock();
        $config->shouldReceive('has')->once()->with('basset::assets.bar.css')->andReturn(false);
        $config->shouldReceive('get')->once()->with('basset::directories')->andReturn(array());
        $filterFactory = $this->getFilterFactoryMock();
        $assetFactory = new AssetFactory($files, $filterFactory, 'path/to/public', 'testing');

        $collection = new Collection('foo', $files, $config, $assetFactory, $filterFactory);

        $asset = $collection->add('bar.css');

        $assets = $collection->getAssets();

        $this->assertEmpty($assets);
        $this->assertEquals(null, $asset->getAbsolutePath());
    }


    public function testFiltersAreAppliedToEntireCollection()
    {
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->twice()->with('path/to/public/bar.css')->andReturn(true);
        $config = $this->getConfigMock();
        $config->shouldReceive('has')->once()->with('basset::assets.bar.css')->andReturn(false);
        $config->shouldReceive('has')->once()->with('basset::filters.FooFilter')->andReturn(false);

        $filterFactory = new FilterFactory($config);
        $assetFactory = new AssetFactory($files, $filterFactory, 'path/to/public', 'testing');
        $collection = new Collection('foo', $files, $config, $assetFactory, $filterFactory);

        $collection->apply('FooFilter');
        $collection->add('bar.css');

        $this->assertArrayHasKey('FooFilter', $collection->getFilters());

        $collection->processCollection();

        $assets = $collection->getAssets();
        $asset = array_pop($assets);

        $this->assertCount(0, $collection->getFilters());
        $this->assertArrayHasKey('FooFilter', $asset->getFilters());
    }


    public function testGetExcludedAssets()
    {
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->twice()->with('path/to/public/bar.css')->andReturn(true);
        $files->shouldReceive('exists')->twice()->with('path/to/public/foo.css')->andReturn(true);
        $config = $this->getConfigMock();
        $config->shouldReceive('has')->once()->with('basset::assets.bar.css')->andReturn(false);
        $config->shouldReceive('has')->once()->with('basset::assets.foo.css')->andReturn(false);
        $filterFactory = $this->getFilterFactoryMock();
        $assetFactory = new AssetFactory($files, $filterFactory, 'path/to/public', 'testing');

        $collection = new Collection('foo', $files, $config, $assetFactory, $filterFactory);

        $collection->add('bar.css')->exclude();
        $collection->add('foo.css');

        $this->assertCount(1, $collection->getExcludedAssets());
        $this->assertCount(2, $collection->getAssets());
    }


    public function testGetExcludedAssetsByGroup()
    {
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->twice()->with('path/to/public/bar.css')->andReturn(true);
        $files->shouldReceive('exists')->twice()->with('path/to/public/foo.js')->andReturn(true);
        $config = $this->getConfigMock();
        $config->shouldReceive('has')->once()->with('basset::assets.bar.css')->andReturn(false);
        $config->shouldReceive('has')->once()->with('basset::assets.foo.js')->andReturn(false);
        $filterFactory = $this->getFilterFactoryMock();

        $assetFactory = m::mock('Basset\AssetFactory[buildAbsolutePath]', array($files, $filterFactory, 'path/to/public', 'testing'));
        $assetFactory->shouldReceive('buildAbsolutePath')->with('path/to/public/bar.css')->andReturn('path/to/public/bar.css');
        $assetFactory->shouldReceive('buildAbsolutePath')->with('path/to/public/foo.js')->andReturn('path/to/public/foo.js');

        $collection = new Collection('foo', $files, $config, $assetFactory, $filterFactory);

        $collection->add('bar.css')->exclude();
        $collection->add('foo.js')->exclude();

        $this->assertCount(1, $collection->getExcludedAssets('styles'));
    }


    public function testCollectionIsBuilt()
    {
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->twice()->with('path/to/public/css/example.css')->andReturn(true);
        $files->shouldReceive('exists')->twice()->with('path/to/public/js/example.js')->andReturn(true);
        $files->shouldReceive('exists')->twice()->with('path/to/public/../css/baz.css')->andReturn(true);
        $files->shouldReceive('getRemote')->once()->with('path/to/public/css/example.css')->andReturn('html { background-color: #fff; }');
        $files->shouldReceive('getRemote')->once()->with('path/to/public/js/example.js')->andReturn('alert("hello world")');
        $files->shouldReceive('getRemote')->once()->with('path/to/css/baz.css')->andReturn('p { font-weight: bold; }');
        $files->shouldReceive('getRemote')->once()->with('http://foo.com/bar.css')->andReturn('a { text-decoration: none; }');
        $config = $this->getConfigMock();
        $config->shouldReceive('has')->once()->with('basset::assets.css/example.css')->andReturn(false);
        $config->shouldReceive('has')->once()->with('basset::assets.js/example.js')->andReturn(false);
        $config->shouldReceive('has')->once()->with('basset::assets.../css/baz.css')->andReturn(false);
        $config->shouldReceive('has')->once()->with('basset::assets.http://foo.com/bar.css')->andReturn(false);

        $filterFactory = new FilterFactory($config);

        $assetFactory = m::mock('Basset\AssetFactory[buildAbsolutePath,buildRelativePath]', array($files, $filterFactory, 'path/to/public', 'testing'));
        $assetFactory->shouldReceive('buildAbsolutePath')->with('path/to/public/css/example.css')->andReturn('path/to/public/css/example.css');
        $assetFactory->shouldReceive('buildAbsolutePath')->with('path/to/public/js/example.js')->andReturn('path/to/public/js/example.js');
        $assetFactory->shouldReceive('buildAbsolutePath')->with('path/to/public/../css/baz.css')->andReturn('path/to/css/baz.css');
        $assetFactory->shouldReceive('buildAbsolutePath')->with('http://foo.com/bar.css')->andReturn('http://foo.com/bar.css');
        $assetFactory->shouldReceive('buildRelativePath')->with('path/to/public/css/example.css')->andReturn('css/example.css');
        $assetFactory->shouldReceive('buildRelativePath')->with('path/to/public/js/example.js')->andReturn('js/example.js');
        $assetFactory->shouldReceive('buildRelativePath')->with('http://foo.com/bar.css')->andReturn('http://foo.com/bar.css');
        $assetFactory->shouldReceive('buildRelativePath')->with('path/to/css/baz.css')->andReturn(md5('path/to/css').'/baz.css');

        $instantiatedFilter = m::mock('Assetic\Filter\FilterInterface');
        $instantiatedFilter->shouldReceive('filterLoad')->times(3)->andReturn(null);
        $instantiatedFilter->shouldReceive('filterDump')->times(3)->andReturnUsing(function($asset)
        {
            $asset->setContent(str_replace('html', 'body', $asset->getContent()));
        });

        $filter = $this->getFilterMock();
        $filter->shouldReceive('getFilter')->times(5)->andReturn('BodyFilter');
        $filter->shouldReceive('getGroupRestriction')->times(4)->andReturn('styles');
        $filter->shouldReceive('getEnvironments')->times(4)->andReturn(array());
        $filter->shouldReceive('instantiate')->times(3)->andReturn($instantiatedFilter);

        $collection = new Collection('foo', $files, $config, $assetFactory, $filterFactory);

        $collection->add('css/example.css');
        $collection->add('js/example.js');
        $collection->add('../css/baz.css');
        $collection->add('http://foo.com/bar.css')->include();

        $collection->apply($filter);

        $builder = new StringBuilder($files, $config);

        $css = $builder->buildStyles($collection);
        $js = $builder->buildScripts($collection);

        $this->assertEquals('body { background-color: #fff; }', $css['css/example.css']);
        $this->assertEquals('p { font-weight: bold; }', $css[md5('path/to/css').'/baz.css']);
        $this->assertEquals('a { text-decoration: none; }', $css['http://foo.com/bar.css']);
        $this->assertEquals('alert("hello world")', $js['js/example.js']);
    }


    public function testBlankDirectoryInstanceReturnedForNonExistentDirectories()
    {
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->twice()->with('foo/bar/baz/qux')->andReturn(false);
        $config = $this->getConfigMock();
        $assetFactory = $this->getAssetFactoryMock();
        $assetFactory->shouldReceive('path')->twice()->with('foo/bar/baz/qux')->andReturn('foo/bar/baz/qux');
        $filterFactory = $this->getFilterFactoryMock();

        $collection = new Collection('foo', $files, $config, $assetFactory, $filterFactory);

        $directory = $collection->requireDirectory('foo/bar/baz/qux');

        $this->assertNull($directory->getPath());

        $directory = $collection->requireTree('foo/bar/baz/qux');

        $this->assertNull($directory->getPath());
    }


    protected function getCollectionInstance()
    {
        $files = $this->getFilesMock();
        $config = $this->getConfigMock();
        $assetFactory = $this->getAssetFactoryMock();
        $filterFactory = $this->getFilterFactoryMock();

        return new Collection('foo', $files, $config, $assetFactory, $filterFactory);
    }


    protected function getFilesMock()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }


    protected function getConfigMock()
    {
        return m::mock('Illuminate\Config\Repository');
    }


    protected function getAssetFactoryMock()
    {
        return m::mock('Basset\AssetFactory');
    }


    protected function getFilterFactoryMock()
    {
        return m::mock('Basset\FilterFactory');
    }


    protected function getFilterMock()
    {
        return m::mock('Basset\Filter\Filter');
    }


    protected function getDirectoryMock()
    {
        return m::mock('Basset\Directory');
    }


}