<?php

use Mockery as m;
use Basset\Factory\FilterFactory;
use Basset\Factory\FactoryManager;

class DirectoryTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testRequireDirectory()
    {
        $files = $this->getFilesMock();
        $manager = $this->getFactoryManager();

        $manager->register('asset', m::mock('Basset\Factory\AssetFactory[buildAbsolutePath,buildRelativePath]', array(
            $files,
            $manager->filter,
            'path/to/public',
            'testing'
        )));
        $manager->asset->shouldReceive('buildAbsolutePath')->once()->with('baz/foo.css')->andReturn('path/to/public/baz/foo.css');
        $manager->asset->shouldReceive('buildAbsolutePath')->once()->with('baz/bar.css')->andReturn('path/to/public/baz/bar.css');
        $manager->asset->shouldReceive('buildRelativePath')->once()->with('path/to/public/baz/foo.css')->andReturn('baz/foo.css');
        $manager->asset->shouldReceive('buildRelativePath')->once()->with('path/to/public/baz/bar.css')->andReturn('baz/bar.css');

        $assets = array(
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo')
        );
        $assets[0]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[1]->shouldReceive('isFile')->once()->andReturn(false);
        $assets[2]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[0]->shouldReceive('getPathname')->once()->andReturn('baz/foo.css');
        $assets[2]->shouldReceive('getPathname')->once()->andReturn('baz/bar.css');

        $directory = m::mock('Basset\Directory[iterateDirectory]', array('baz', $files, $manager));
        $directory->shouldReceive('iterateDirectory')->once()->with('baz')->andReturn($assets);
        $directory->requireDirectory();

        $assets = $directory->getAssets();

        $this->assertEquals('baz/foo.css', $assets[0]->getRelativePath());
        $this->assertEquals('baz/bar.css', $assets[1]->getRelativePath());
    }


    public function testRecursivelyRequireDirectory()
    {
        $files = $this->getFilesMock();
        $filterFactory = $this->getFilterFactoryMock();

        $manager = $this->getFactoryManager();

        $manager->register('asset', m::mock('Basset\Factory\AssetFactory[buildAbsolutePath,buildRelativePath]', array(
            $files,
            $manager->filter,
            'path/to/public',
            'testing'
        )));
        $manager->asset->shouldReceive('buildAbsolutePath')->once()->with('baz/foo.css')->andReturn('path/to/public/baz/foo.css');
        $manager->asset->shouldReceive('buildAbsolutePath')->once()->with('baz/qux/bar.css')->andReturn('path/to/public/baz/qux/bar.css');
        $manager->asset->shouldReceive('buildRelativePath')->once()->with('path/to/public/baz/foo.css')->andReturn('baz/foo.css');
        $manager->asset->shouldReceive('buildRelativePath')->once()->with('path/to/public/baz/qux/bar.css')->andReturn('baz/qux/bar.css');

        $assets = array(
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo')
        );

        $assets[0]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[1]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[0]->shouldReceive('getPathname')->once()->andReturn('baz/foo.css');
        $assets[1]->shouldReceive('getPathname')->once()->andReturn('baz/qux/bar.css');

        $directory = m::mock('Basset\Directory[recursivelyIterateDirectory]', array('baz', $files, $manager));
        $directory->shouldReceive('recursivelyIterateDirectory')->once()->with('baz')->andReturn($assets);
        $directory->requireTree();

        $assets = $directory->getAssets();

        $this->assertEquals('baz/foo.css', $assets[0]->getRelativePath());
        $this->assertEquals('baz/qux/bar.css', $assets[1]->getRelativePath());
    }


    public function testExcludeAndRequireSpecificFilesInDirectory()
    {
        $files = $this->getFilesMock();
        $manager = $this->getFactoryManager();

        $manager->register('asset', m::mock('Basset\Factory\AssetFactory[buildAbsolutePath,buildRelativePath]', array(
            $files,
            $manager->filter,
            'path/to/public',
            'testing'
        )));
        $manager->asset->shouldReceive('buildAbsolutePath')->once()->with('baz/foo.css')->andReturn('path/to/public/baz/foo.css');
        $manager->asset->shouldReceive('buildAbsolutePath')->once()->with('baz/qux/bar.css')->andReturn('path/to/public/baz/qux/bar.css');
        $manager->asset->shouldReceive('buildRelativePath')->once()->with('path/to/public/baz/foo.css')->andReturn('baz/foo.css');
        $manager->asset->shouldReceive('buildRelativePath')->once()->with('path/to/public/baz/qux/bar.css')->andReturn('baz/qux/bar.css');

        $assets = array(
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo')
        );
        $assets[0]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[1]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[0]->shouldReceive('getPathname')->once()->andReturn('baz/foo.css');
        $assets[1]->shouldReceive('getPathname')->once()->andReturn('baz/qux/bar.css');

        $directory = m::mock('Basset\Directory[recursivelyIterateDirectory]', array('baz', $files, $manager));
        $directory->shouldReceive('recursivelyIterateDirectory')->once()->with('baz')->andReturn($assets);
        $directory->requireTree()->only('baz/foo.css');

        $assets = $directory->getAssets();

        $this->assertEquals('baz/foo.css', $assets[0]->getRelativePath());

        $directory->except('baz/foo.css');

        $this->assertEmpty($directory->getAssets());
    }


    public function testFiltersAreAppliedToEntireDirectory()
    {
        $files = $this->getFilesMock();
        $config = $this->getConfigMock();
        $config->shouldReceive('has')->once()->with('basset::filters.FooFilter')->andReturn(false);

        $manager = $this->getFactoryManager();

        $manager->register('filter', new FilterFactory($config));

        $manager->register('asset', m::mock('Basset\Factory\AssetFactory[buildAbsolutePath,buildRelativePath]', array(
            $files,
            $manager->filter,
            'path/to/public',
            'testing'
        )));
        $manager->asset->shouldReceive('buildAbsolutePath')->once()->with('baz/foo.css')->andReturn('path/to/public/baz/foo.css');
        $manager->asset->shouldReceive('buildAbsolutePath')->once()->with('baz/bar.css')->andReturn('path/to/public/baz/bar.css');
        $manager->asset->shouldReceive('buildRelativePath')->once()->with('path/to/public/baz/foo.css')->andReturn('baz/foo.css');
        $manager->asset->shouldReceive('buildRelativePath')->once()->with('path/to/public/baz/bar.css')->andReturn('baz/bar.css');
        $assets = array(
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo')
        );
        $assets[0]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[1]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[0]->shouldReceive('getPathname')->once()->andReturn('baz/foo.css');
        $assets[1]->shouldReceive('getPathname')->once()->andReturn('baz/bar.css');

        $directory = m::mock('Basset\Directory[iterateDirectory]', array('baz', $files, $manager));
        $directory->shouldReceive('iterateDirectory')->once()->with('baz')->andReturn($assets);
        $directory->requireDirectory()->apply('FooFilter');

        $directory->processFilters();

        $assets = $directory->getAssets();

        $filters = $assets[0]->getFilters();
        $this->assertInstanceOf('Basset\Filter\Filter', $filters['FooFilter']);

        $filters = $assets[1]->getFilters();
        $this->assertInstanceOf('Basset\Filter\Filter', $filters['FooFilter']);
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
        return m::mock('Basset\Factory\AssetFactory');
    }


    protected function getFilterFactoryMock()
    {
        return m::mock('Basset\Factory\FilterFactory');
    }


    protected function getFactoryManager()
    {
        $manager = new FactoryManager;

        $manager->register('asset', $this->getAssetFactoryMock());
        $manager->register('filter', $this->getFilterFactoryMock());

        return $manager;
    }


}