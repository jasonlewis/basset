<?php

use Mockery as m;

class DirectoryTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testCanRequireDirectory()
    {
        $files = $this->getFiles();
        $manager = m::mock('JasonLewis\Basset\AssetManager[getAbsolutePath]', array($files, 'path/to/public', 'local'));
        $manager->shouldReceive('getAbsolutePath')->once()->with('css/example.css')->andReturn('css/example.css');
        $manager->shouldReceive('getAbsolutePath')->once()->with('css/foobar.css')->andReturn('css/foobar.css');
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
        $directory = m::mock('JasonLewis\Basset\Directory[iterateDirectory]', array($files, $manager, 'css'));
        $directory->shouldReceive('iterateDirectory')->once()->with('css')->andReturn($assets);
        $directory->requireDirectory();
        $assets = $directory->getAssets();
        $this->assertCount(2, $assets);
        $this->assertInstanceOf('JasonLewis\Basset\Asset', $assets[0]);
        $this->assertInstanceOf('JasonLewis\Basset\Asset', $assets[1]);
        $this->assertEquals('css/example.css', $assets[0]->getAbsolutePath());
    }


    public function testCanRecursivelyRequireDirectory()
    {
        $files = $this->getFiles();
        $manager = m::mock('JasonLewis\Basset\AssetManager[getAbsolutePath]', array($files, 'path/to/public', 'local'));
        $manager->shouldReceive('getAbsolutePath')->once()->with('css/example.css')->andReturn('css/example.css');
        $manager->shouldReceive('getAbsolutePath')->once()->with('css/nested/foobar.css')->andReturn('css/nested/foobar.css');
        $assets = array(
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo')
        );
        $assets[0]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[1]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[0]->shouldReceive('getPathname')->once()->andReturn('css/example.css');
        $assets[1]->shouldReceive('getPathname')->once()->andReturn('css/nested/foobar.css');
        $directory = m::mock('JasonLewis\Basset\Directory[recursivelyIterateDirectory]', array($files, $manager, 'css'));
        $directory->shouldReceive('recursivelyIterateDirectory')->once()->with('css')->andReturn($assets);
        $directory->requireTree();
        $assets = $directory->getAssets();
        $this->assertCount(2, $assets);
        $this->assertInstanceOf('JasonLewis\Basset\Asset', $assets[0]);
        $this->assertInstanceOf('JasonLewis\Basset\Asset', $assets[1]);
        $this->assertEquals('css/nested/foobar.css', $assets[1]->getAbsolutePath());
    }


    public function testCanExcludeAndRequireSpecificFilesInDirectory()
    {
        $files = $this->getFiles();
        $manager = m::mock('JasonLewis\Basset\AssetManager[getAbsolutePath]', array($files, 'path/to/public', 'local'));
        $manager->shouldReceive('getAbsolutePath')->once()->with('css/example.css')->andReturn('css/example.css');
        $manager->shouldReceive('getAbsolutePath')->once()->with('css/nested/foobar.css')->andReturn('css/nested/foobar.css');
        $assets = array(
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo')
        );
        $assets[0]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[1]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[0]->shouldReceive('getPathname')->once()->andReturn('css/example.css');
        $assets[1]->shouldReceive('getPathname')->once()->andReturn('css/nested/foobar.css');
        $directory = m::mock('JasonLewis\Basset\Directory[recursivelyIterateDirectory]', array($files, $manager, 'css'));
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
        $manager = m::mock('JasonLewis\Basset\AssetManager[getAbsolutePath]', array($files, 'path/to/public', 'local'));
        $manager->shouldReceive('getAbsolutePath')->once()->with('css/example.css')->andReturn('css/example.css');
        $manager->shouldReceive('getAbsolutePath')->once()->with('css/foobar.css')->andReturn('css/foobar.css');
        $assets = array(
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo')
        );
        $assets[0]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[1]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[0]->shouldReceive('getPathname')->once()->andReturn('css/example.css');
        $assets[1]->shouldReceive('getPathname')->once()->andReturn('css/foobar.css');
        $directory = m::mock('JasonLewis\Basset\Directory[iterateDirectory]', array($files, $manager, 'css'));
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


    protected function getManager()
    {
        return m::mock('JasonLewis\Basset\AssetManager');
    }


}