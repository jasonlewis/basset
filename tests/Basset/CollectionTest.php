<?php

use Mockery as m;
use JasonLewis\Basset\Collection;
use JasonLewis\Basset\AssetFactory;
use JasonLewis\Basset\FilterFactory;
use JasonLewis\Basset\Compiler\StringCompiler;

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

        $collection = new Collection($files, $config, 'foo');

        $collection->setAssetFactory($assetFactory);
        $collection->setFilterFactory($filterFactory);

        $collection->add('bar.css');

        $assets = $collection->getAssets();

        $this->assertInstanceOf('JasonLewis\Basset\Asset', array_pop($assets));
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

        $collection = new Collection($files, $config, 'foo');

        $collection->setAssetFactory($assetFactory);
        $collection->setFilterFactory($filterFactory);

        $collection->add('foo');

        $assets = $collection->getAssets();

        $this->assertInstanceOf('JasonLewis\Basset\Asset', array_pop($assets));
    }


    public function testAddAssetFromRemoteLocation()
    {
        $files = $this->getFilesMock();
        $config = $this->getConfigMock();
        $config->shouldReceive('has')->once()->with('basset::assets.http://foo.com/bar.css')->andReturn(false);
        $filterFactory = $this->getFilterFactoryMock();

        $assetFactory = new AssetFactory($files, $filterFactory, 'path/to/public', 'testing');

        $collection = new Collection($files, $config, 'foo');

        $collection->setAssetFactory($assetFactory);
        $collection->setFilterFactory($filterFactory);

        $collection->add('http://foo.com/bar.css');

        $assets = $collection->getAssets();
        $asset = array_pop($assets);

        $this->assertTrue($asset->isRemote());
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

        $directory = m::mock('JasonLewis\Basset\Directory[recursivelyIterateDirectory]', array($files, $assetFactory, $filterFactory, 'path/to/public/nested'));
        $directory->shouldReceive('recursivelyIterateDirectory')->once()->with('path/to/public/nested')->andReturn(array($file));

        $collection = m::mock('JasonLewis\Basset\Collection[parseDirectoryPath]', array($files, $config, 'foo'));
        $collection->shouldReceive('parseDirectoryPath')->with('path/to/public/nested')->andReturn($directory);

        $collection->setAssetFactory($assetFactory);
        $collection->setFilterFactory($filterFactory);

        $collection->add('bar.css');

        $assets = $collection->getAssets();

        $this->assertInstanceOf('JasonLewis\Basset\Asset', array_pop($assets));
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

        $collection = m::mock('JasonLewis\Basset\Collection[parseDirectoryPath]', array($files, $config, 'foo'));
        $collection->shouldReceive('parseDirectoryPath')->with('nested')->andReturn($directory);

        $collection->setAssetFactory($assetFactory);
        $collection->setFilterFactory($filterFactory);

        $collection->directory('nested', function($collection)
        {
            $collection->add('bar.css');
        });

        $assets = $collection->getAssets();

        $this->assertInstanceOf('JasonLewis\Basset\Asset', array_pop($assets));
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

        $collection = new Collection($files, $config, 'foo');

        $collection->setAssetFactory($assetFactory);
        $collection->setFilterFactory($filterFactory);

        $collection->apply('FooFilter');

        $collection->add('bar.css');

        $this->assertArrayHasKey('FooFilter', $collection->getFilters());

        $collection->processCollection();

        $assets = $collection->getAssets();
        $asset = array_pop($assets);

        $this->assertCount(0, $collection->getFilters());
        $this->assertArrayHasKey('FooFilter', $asset->getFilters());
    }


    public function testGetIgnoredAssets()
    {
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->twice()->with('path/to/public/bar.css')->andReturn(true);
        $files->shouldReceive('exists')->twice()->with('path/to/public/foo.css')->andReturn(true);
        $config = $this->getConfigMock();
        $config->shouldReceive('has')->once()->with('basset::assets.bar.css')->andReturn(false);
        $config->shouldReceive('has')->once()->with('basset::assets.foo.css')->andReturn(false);
        $filterFactory = $this->getFilterFactoryMock();

        $assetFactory = new AssetFactory($files, $filterFactory, 'path/to/public', 'testing');

        $collection = new Collection($files, $config, 'foo');

        $collection->setAssetFactory($assetFactory);
        $collection->setFilterFactory($filterFactory);

        $collection->add('bar.css')->ignore();
        $collection->add('foo.css');

        $this->assertCount(1, $collection->getIgnoredAssets());
        $this->assertCount(2, $collection->getAssets());
    }


    public function testGetIgnoredAssetsByGroup()
    {
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->twice()->with('path/to/public/bar.css')->andReturn(true);
        $files->shouldReceive('exists')->twice()->with('path/to/public/foo.js')->andReturn(true);
        $config = $this->getConfigMock();
        $config->shouldReceive('has')->once()->with('basset::assets.bar.css')->andReturn(false);
        $config->shouldReceive('has')->once()->with('basset::assets.foo.js')->andReturn(false);
        $filterFactory = $this->getFilterFactoryMock();

        $assetFactory = m::mock('JasonLewis\Basset\AssetFactory[getAbsolutePath]', array($files, $filterFactory, 'path/to/public', 'testing'));
        $assetFactory->shouldReceive('getAbsolutePath')->with('path/to/public/bar.css')->andReturn('path/to/public/bar.css');
        $assetFactory->shouldReceive('getAbsolutePath')->with('path/to/public/foo.js')->andReturn('path/to/public/foo.js');

        $collection = new Collection($files, $config, 'foo');

        $collection->setAssetFactory($assetFactory);
        $collection->setFilterFactory($filterFactory);

        $collection->add('bar.css')->ignore();
        $collection->add('foo.js')->ignore();

        $this->assertCount(1, $collection->getIgnoredAssets('styles'));
    }


    public function testCollectionIsCompiled()
    {
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->twice()->with('path/to/public/css/example.css')->andReturn(true);
        $files->shouldReceive('exists')->twice()->with('path/to/public/js/example.js')->andReturn(true);
        $files->shouldReceive('getRemote')->once()->with('path/to/public/css/example.css')->andReturn('html { background-color: #fff; }');
        $files->shouldReceive('getRemote')->once()->with('path/to/public/js/example.js')->andReturn('alert("hello world")');
        $files->shouldReceive('getRemote')->once()->with('http://foo.com/bar.css')->andReturn('a { text-decoration: none; }');
        $config = $this->getConfigMock();
        $config->shouldReceive('has')->once()->with('basset::assets.css/example.css')->andReturn(false);
        $config->shouldReceive('has')->once()->with('basset::assets.js/example.js')->andReturn(false);
        $config->shouldReceive('has')->once()->with('basset::assets.http://foo.com/bar.css')->andReturn(false);
        $config->shouldReceive('get')->once()->with('basset::compile_remotes', false)->andReturn(true);

        $filterFactory = new FilterFactory($config);

        $assetFactory = m::mock('JasonLewis\Basset\AssetFactory[getAbsolutePath]', array($files, $filterFactory, 'path/to/public', 'testing'));
        $assetFactory->shouldReceive('getAbsolutePath')->with('path/to/public/css/example.css')->andReturn('path/to/public/css/example.css');
        $assetFactory->shouldReceive('getAbsolutePath')->with('path/to/public/js/example.js')->andReturn('path/to/public/js/example.js');
        $assetFactory->shouldReceive('getAbsolutePath')->with('http://foo.com/bar.css')->andReturn('http://foo.com/bar.css');

        $instantiatedFilter = m::mock('Assetic\Filter\FilterInterface');
        $instantiatedFilter->shouldReceive('filterLoad')->twice()->andReturn(null);
        $instantiatedFilter->shouldReceive('filterDump')->twice()->andReturnUsing(function($asset)
        {
            $asset->setContent(str_replace('html', 'body', $asset->getContent()));
        });

        $filter = $this->getFilterMock();
        $filter->shouldReceive('getFilter')->times(4)->andReturn('BodyFilter');
        $filter->shouldReceive('getGroupRestriction')->times(3)->andReturn('styles');
        $filter->shouldReceive('getEnvironments')->times(3)->andReturn(array());
        $filter->shouldReceive('instantiate')->twice()->andReturn($instantiatedFilter);

        $collection = new Collection($files, $config, 'foo');

        $collection->setAssetFactory($assetFactory);
        $collection->setFilterFactory($filterFactory);

        $collection->add('css/example.css');
        $collection->add('js/example.js');
        $collection->add('http://foo.com/bar.css');
        $collection->apply($filter);

        $compiler = new StringCompiler($files, $config);

        $css = $compiler->compileStyles($collection);
        $js = $compiler->compileScripts($collection);

        $this->assertEquals('body { background-color: #fff; }', $css['path/to/public/css/example.css']);
        $this->assertEquals('a { text-decoration: none; }', $css['http://foo.com/bar.css']);
        $this->assertEquals('alert("hello world")', $js['path/to/public/js/example.js']);
    }


    protected function getCollectionInstance()
    {
        $files = $this->getFilesMock();
        $config = $this->getConfigMock();
        $assetFactory = $this->getAssetFactoryMock();
        $filterFactory = $this->getFilterFactoryMock();

        $collection = new Collection($files, $config, 'foo');

        $collection->setAssetFactory($assetFactory);
        $collection->setFilterFactory($filterFactory);

        return $collection;
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
        return m::mock('JasonLewis\Basset\AssetFactory');
    }


    protected function getFilterFactoryMock()
    {
        return m::mock('JasonLewis\Basset\FilterFactory');
    }


    protected function getFilterMock()
    {
        return m::mock('JasonLewis\Basset\Filter');
    }


    protected function getDirectoryMock()
    {
        return m::mock('JasonLewis\Basset\Directory');
    }


}