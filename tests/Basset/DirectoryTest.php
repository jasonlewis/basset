<?php

use Mockery as m;
use Basset\Asset;
use Basset\Directory;
use Basset\AssetFinder;

class DirectoryTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function setUp()
    {
        $this->log = m::mock('Illuminate\Log\Writer');
        $this->files = m::mock('Illuminate\Filesystem\Filesystem');
        $this->finder = m::mock('Basset\AssetFinder');
        $this->filter = m::mock('Basset\Factory\FilterFactory');
        $this->asset = m::mock('Basset\Factory\AssetFactory', array($this->files, $this->filter, 'path/to/public'))->shouldDeferMissing();

        $this->directory = new Directory($this->log, $this->asset, $this->filter, $this->finder, 'foo');
    }


    public function testAddingBasicAssetFromPublicDirectory()
    {
        $asset = new Asset($this->files, $this->filter, null, null);

        $this->finder->shouldReceive('find')->once()->with('foo.css')->andReturn('path/to/foo.css');
        $this->asset->shouldReceive('make')->once()->with('path/to/foo.css')->andReturn($asset);

        $this->assertInstanceOf('Basset\Asset', $this->directory->stylesheet('foo.css'));
        $this->assertCount(1, $this->directory->getDirectoryAssets());
    }


    public function testAddingInvalidAssetReturnsBlankAssetInstance()
    {
        $asset = new Asset($this->files, $this->filter, null, null);

        $this->finder->shouldReceive('find')->once()->with('foo.css')->andThrow('Basset\Exceptions\AssetNotFoundException');
        $this->asset->shouldReceive('make')->once()->with(null)->andReturn($asset);

        $this->log->shouldReceive('error')->once();

        $this->assertInstanceOf('Basset\Asset', $this->directory->stylesheet('foo.css'));
        $this->assertCount(0, $this->directory->getDirectoryAssets());
    }


    public function testAddingAssetFiresCallback()
    {
        $asset = new Asset($this->files, $this->filter, null, null);

        $this->finder->shouldReceive('find')->once()->with('foo.js')->andReturn('path/to/foo.js');
        $this->asset->shouldReceive('make')->once()->with('path/to/foo.js')->andReturn($asset);

        $fired = false;

        $this->directory->javascript('foo.js', function() use (&$fired) { $fired = true; });
        $this->assertTrue($fired);
    }


    public function testChangingTheWorkingDirectory()
    {
        $this->finder = new AssetFinder($this->files, m::mock('Illuminate\Config\Repository'), 'path/to/public');
        $this->directory = new Directory($this->log, $this->asset, $this->filter, $this->finder, 'foo');

        $this->files->shouldReceive('exists')->once()->with('path/to/public/css')->andReturn(true);

        $this->assertInstanceOf('Basset\Directory', $this->directory->directory('css'));
    }


    public function testChangingTheWorkingDirectoryToInvalidDirectoryReturnsBlankDirectoryInstance()
    {
        $this->finder = new AssetFinder($this->files, m::mock('Illuminate\Config\Repository'), 'path/to/public');
        $this->directory = new Directory($this->log, $this->asset, $this->filter, $this->finder, 'foo');

        $this->files->shouldReceive('exists')->once()->with('path/to/public/css')->andReturn(false);

        $this->log->shouldReceive('error')->once();

        $this->assertInstanceOf('Basset\Directory', $this->directory->directory('css'));
    }


    public function testChangingTheWorkingDirectoryFiresCallback()
    {
        $this->finder = new AssetFinder($this->files, m::mock('Illuminate\Config\Repository'), 'path/to/public');
        $this->directory = new Directory($this->log, $this->asset, $this->filter, $this->finder, 'foo');

        $this->files->shouldReceive('exists')->once()->with('path/to/public/css')->andReturn(true);

        $fired = false;
        $this->directory->directory('css', function() use (&$fired) { $fired = true; });
        $this->assertTrue($fired);
    }


    public function testRequireCurrentWorkingDirectory()
    {
        $this->directory = m::mock('Basset\Directory[iterateDirectory]', array($this->log, $this->asset, $this->filter, $this->finder, 'foo'));
        $this->directory->shouldReceive('iterateDirectory')->once()->with('foo')->andReturn($iterator = m::mock('Iterator'));

        $iterator->shouldReceive('rewind')->once();
        $iterator->shouldReceive('valid')->times()->andReturn(true, true, false);
        $iterator->shouldReceive('current')->once()->andReturn($files[] = m::mock('SplFileInfo'));
        $iterator->shouldReceive('current')->once()->andReturn($files[] = m::mock('SplFileInfo'));
        $iterator->shouldReceive('next')->twice();

        $files[0]->shouldReceive('isFile')->andReturn(true);
        $files[0]->shouldReceive('getPathname')->andReturn('foo/bar.css');
        $files[1]->shouldReceive('isFile')->andReturn(false);

        $asset = new Asset($this->files, $this->filter, null, null);
        $this->finder->shouldReceive('find')->once()->with('foo/bar.css')->andReturn('foo/bar.css');
        $this->asset->shouldReceive('make')->once()->with('foo/bar.css')->andReturn($asset);

        $this->directory->requireDirectory();
        $this->assertCount(1, $this->directory->getDirectoryAssets());
    }


    public function testRequireDirectoryChangesDirectoryAndRequiresNewWorkingDirectory()
    {
        $this->directory = m::mock('Basset\Directory[directory]', array($this->log, $this->asset, $this->filter, $this->finder, 'foo'));

        $requireDirectory = m::mock('Basset\Directory[iterateDirectory]', array($this->log, $this->asset, $this->filter, $this->finder, 'foo/bar'));
        $this->directory->shouldReceive('directory')->with('bar')->andReturn($requireDirectory);

        $requireDirectory->shouldReceive('iterateDirectory')->once()->with('foo/bar')->andReturn($iterator = m::mock('Iterator'));

        $iterator->shouldReceive('rewind')->once();
        $iterator->shouldReceive('valid')->twice()->andReturn(true, false);
        $iterator->shouldReceive('current')->once()->andReturn($file = m::mock('SplFileInfo'));
        $iterator->shouldReceive('next')->once();

        $file->shouldReceive('isFile')->andReturn(true);
        $file->shouldReceive('getPathname')->andReturn('foo/bar/baz.css');

        $asset = new Asset($this->files, $this->filter, null, null);
        $this->finder->shouldReceive('find')->once()->with('foo/bar/baz.css')->andReturn('foo/bar/baz.css');
        $this->asset->shouldReceive('make')->once()->with('foo/bar/baz.css')->andReturn($asset);

        $this->directory->requireDirectory('bar');
        $this->assertCount(1, $requireDirectory->getDirectoryAssets());
    }


    public function testRequireCurrentWorkingDirectoryTree()
    {
        $this->directory = m::mock('Basset\Directory[recursivelyIterateDirectory]', array($this->log, $this->asset, $this->filter, $this->finder, 'foo'));
        $this->directory->shouldReceive('recursivelyIterateDirectory')->once()->with('foo')->andReturn($iterator = m::mock('Iterator'));

        $iterator->shouldReceive('rewind')->once();
        $iterator->shouldReceive('valid')->once()->andReturn(false);

        $this->directory->requireTree();
        $this->assertCount(0, $this->directory->getDirectoryAssets());
    }


    public function testRequireTreeChangesWorkingDirectoryAndRequiresNewDirectoryTree()
    {
        $this->directory = m::mock('Basset\Directory[directory]', array($this->log, $this->asset, $this->filter, $this->finder, 'foo'));

        $requireTree = m::mock('Basset\Directory[recursivelyIterateDirectory]', array($this->log, $this->asset, $this->filter, $this->finder, 'foo/bar'));
        $this->directory->shouldReceive('directory')->once()->with('bar')->andReturn($requireTree);

        $requireTree->shouldReceive('recursivelyIterateDirectory')->once()->with('foo/bar')->andReturn($iterator = m::mock('Iterator'));

        $iterator->shouldReceive('rewind')->once();
        $iterator->shouldReceive('valid')->once()->andReturn(false);

        $this->directory->requireTree('bar');
        $this->assertCount(0, $this->directory->getDirectoryAssets());
    }


    public function testCanGetFilesystemIterator()
    {
        $this->assertInstanceOf('FilesystemIterator', $this->directory->iterateDirectory(__DIR__));
    }


    public function testCanGetRecursiveDirectoryIterator()
    {
        $this->assertInstanceOf('RecursiveIteratorIterator', $this->directory->recursivelyIterateDirectory(__DIR__));
    }


    public function testGettingIteratorsReturnsFalseForInvalidDirectories()
    {
        $this->assertFalse($this->directory->iterateDirectory('foo'));
        $this->assertFalse($this->directory->recursivelyIterateDirectory('foo'));
    }


    public function testGettingOfDirectoryPath()
    {
        $this->assertEquals('foo', $this->directory->getPath());
    }


    public function testExcludingOfAssetsFromDirectory()
    {
        $fooAsset = new Asset($this->files, $this->filter, 'path/to/foo.css', 'foo.css');
        $fooAsset->setOrder(1);
        $barAsset = new Asset($this->files, $this->filter, 'path/to/bar.css', 'bar.css');
        $barAsset->setOrder(1);

        $this->finder->shouldReceive('find')->once()->with('foo.css')->andReturn('path/to/foo.css');
        $this->asset->shouldReceive('make')->once()->with('path/to/foo.css')->andReturn($fooAsset);

        $this->finder->shouldReceive('find')->once()->with('bar.css')->andReturn('path/to/bar.css');
        $this->asset->shouldReceive('make')->once()->with('path/to/bar.css')->andReturn($barAsset);

        $this->directory->stylesheet('foo.css');
        $this->directory->stylesheet('bar.css');

        $this->directory->except('foo.css');

        $this->assertEquals($barAsset, $this->directory->getDirectoryAssets()->first());
    }


    public function testIncludingOfAssetsFromDirectory()
    {
        $fooAsset = new Asset($this->files, $this->filter, 'path/to/foo.css', 'foo.css');
        $fooAsset->setOrder(1);
        $barAsset = new Asset($this->files, $this->filter, 'path/to/bar.css', 'bar.css');
        $barAsset->setOrder(1);

        $this->finder->shouldReceive('find')->once()->with('foo.css')->andReturn('path/to/foo.css');
        $this->asset->shouldReceive('make')->once()->with('path/to/foo.css')->andReturn($fooAsset);

        $this->finder->shouldReceive('find')->once()->with('bar.css')->andReturn('path/to/bar.css');
        $this->asset->shouldReceive('make')->once()->with('path/to/bar.css')->andReturn($barAsset);

        $this->directory->stylesheet('foo.css');
        $this->directory->stylesheet('bar.css');

        $this->directory->only('foo.css');

        $this->assertEquals($fooAsset, $this->directory->getDirectoryAssets()->first());
    }


    public function testGetAssetsFromDirectory()
    {
        $this->assertCount(0, $this->directory->getAssets());
    }


    public function testGetAssetsFromDirectoryAndChildDirectories()
    {
        $this->finder = new AssetFinder($this->files, m::mock('Illuminate\Config\Repository'), 'path/to/public');
        $this->directory = new Directory($this->log, $this->asset, $this->filter, $this->finder, 'foo');

        $this->files->shouldReceive('exists')->once()->with('path/to/public/css')->andReturn(true);

        $this->directory->directory('css');

        $this->assertCount(0, $this->directory->getAssets());
    }


}