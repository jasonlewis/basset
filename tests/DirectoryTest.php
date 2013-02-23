<?php

use Mockery as m;

class DirectoryTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testRequireDirectory()
    {
        $files = $this->getFiles();
        $filterFactory = $this->getFilterFactory();
        $assetFactory = m::mock('JasonLewis\Basset\AssetFactory[getAbsolutePath]', array($files, $filterFactory, 'path/to/public', 'local'));
        $assetFactory->shouldReceive('getAbsolutePath')->once()->with('css/example.css')->andReturn('css/example.css');
        $assetFactory->shouldReceive('getAbsolutePath')->once()->with('css/foobar.css')->andReturn('css/foobar.css');
        $assets = array(
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo')
        );
        $assets[0]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[1]->shouldReceive('isFile')->once()->andReturn(false);
        $assets[2]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[0]->shouldReceive('getPathname')->once()->andReturn('css/example.css');
        $assets[2]->shouldReceive('getPathname')->once()->andReturn('css/foobar.css');
        $directory = m::mock('JasonLewis\Basset\Directory[iterateDirectory]', array($files, $assetFactory, $filterFactory, 'css'));
        $directory->shouldReceive('iterateDirectory')->once()->with('css')->andReturn($assets);
        $directory->requireDirectory();
        $assets = $directory->getAssets();
        $this->assertInstanceOf('JasonLewis\Basset\Asset', $assets[0]);
        $this->assertInstanceOf('JasonLewis\Basset\Asset', $assets[1]);
        $this->assertEquals('css/example.css', $assets[0]->getAbsolutePath());
    }


    public function testRecursivelyRequireDirectory()
    {
        $files = $this->getFiles();
        $filterFactory = $this->getFilterFactory();
        $assetFactory = m::mock('JasonLewis\Basset\AssetFactory[getAbsolutePath]', array($files, $filterFactory, 'path/to/public', 'local'));
        $assetFactory->shouldReceive('getAbsolutePath')->once()->with('css/example.css')->andReturn('css/example.css');
        $assetFactory->shouldReceive('getAbsolutePath')->once()->with('css/nested/foobar.css')->andReturn('css/nested/foobar.css');
        $assets = array(
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo')
        );
        $assets[0]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[1]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[0]->shouldReceive('getPathname')->once()->andReturn('css/example.css');
        $assets[1]->shouldReceive('getPathname')->once()->andReturn('css/nested/foobar.css');
        $directory = m::mock('JasonLewis\Basset\Directory[recursivelyIterateDirectory]', array($files, $assetFactory, $filterFactory, 'css'));
        $directory->shouldReceive('recursivelyIterateDirectory')->once()->with('css')->andReturn($assets);
        $directory->requireTree();
        $assets = $directory->getAssets();
        $this->assertInstanceOf('JasonLewis\Basset\Asset', $assets[0]);
        $this->assertInstanceOf('JasonLewis\Basset\Asset', $assets[1]);
        $this->assertEquals('css/nested/foobar.css', $assets[1]->getAbsolutePath());
    }


    public function testExcludeAndRequireSpecificFilesInDirectory()
    {
        $files = $this->getFiles();
        $filterFactory = $this->getFilterFactory();
        $assetFactory = m::mock('JasonLewis\Basset\AssetFactory[getAbsolutePath]', array($files, $filterFactory, 'path/to/public', 'local'));
        $assetFactory->shouldReceive('getAbsolutePath')->once()->with('css/example.css')->andReturn('css/example.css');
        $assetFactory->shouldReceive('getAbsolutePath')->once()->with('css/nested/foobar.css')->andReturn('css/nested/foobar.css');
        $assets = array(
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo')
        );
        $assets[0]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[1]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[0]->shouldReceive('getPathname')->once()->andReturn('css/example.css');
        $assets[1]->shouldReceive('getPathname')->once()->andReturn('css/nested/foobar.css');
        $directory = m::mock('JasonLewis\Basset\Directory[recursivelyIterateDirectory]', array($files, $assetFactory, $filterFactory, 'css'));
        $directory->shouldReceive('recursivelyIterateDirectory')->once()->with('css')->andReturn($assets);
        $directory->requireTree()->only('css/example.css');
        $assets = $directory->getAssets();
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('JasonLewis\Basset\Asset', $assets[0]);
        $this->assertEquals('css/example.css', $assets[0]->getAbsolutePath());
        $directory->except('css/example.css');
        $assets = $directory->getAssets();
        $this->assertCount(0, $assets);
    }


    public function testFiltersAreAppliedToEntireDirectory()
    {
        $files = $this->getFiles();
        $config = $this->getConfig();
        $config->shouldReceive('has')->once()->with('basset::filters.FooFilter')->andReturn(false);
        $filterFactory = new JasonLewis\Basset\FilterFactory($config);
        $assetFactory = m::mock('JasonLewis\Basset\AssetFactory[getAbsolutePath]', array($files, $filterFactory, 'path/to/public', 'local'));
        $assetFactory->shouldReceive('getAbsolutePath')->once()->with('css/example.css')->andReturn('css/example.css');
        $assetFactory->shouldReceive('getAbsolutePath')->once()->with('css/foobar.css')->andReturn('css/foobar.css');
        $assets = array(
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo')
        );
        $assets[0]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[1]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[0]->shouldReceive('getPathname')->once()->andReturn('css/example.css');
        $assets[1]->shouldReceive('getPathname')->once()->andReturn('css/foobar.css');
        $directory = m::mock('JasonLewis\Basset\Directory[iterateDirectory]', array($files, $assetFactory, $filterFactory, 'css'));
        $directory->shouldReceive('iterateDirectory')->once()->with('css')->andReturn($assets);
        $directory->requireDirectory()->apply('FooFilter');
        $directory->processFilters();
        $assets = $directory->getAssets();
        $this->assertCount(1, $assets[0]->getFilters());
        $this->assertCount(1, $assets[1]->getFilters());
        $filters = $assets[0]->getFilters();
        $this->assertArrayHasKey('FooFilter', $filters);
        $this->assertInstanceOf('JasonLewis\Basset\Filter', $filters['FooFilter']);
        $filters = $assets[1]->getFilters();
        $this->assertArrayHasKey('FooFilter', $filters);
        $this->assertInstanceOf('JasonLewis\Basset\Filter', $filters['FooFilter']);
    }


    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }


    protected function getConfig()
    {
        return m::mock('Illuminate\Config\Repository');
    }


    protected function getAssetFactory()
    {
        return m::mock('JasonLewis\Basset\AssetFactory');
    }


    protected function getFilterFactory()
    {
        return m::mock('JasonLewis\Basset\FilterFactory');
    }


}