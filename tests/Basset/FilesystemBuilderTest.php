<?php

use Mockery as m;
use Basset\Builder\FilesystemBuilder;

class FilesystemBuilderTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testCollectionsAreBuiltWithFilesystemBuilder()
    {
        $assets = array(
            $this->getAssetMock(),
            $this->getAssetMock()
        );
        $assets[0]->shouldReceive('isIgnored')->once()->andReturn(false);
        $assets[0]->shouldReceive('isRemote')->once()->andReturn(false);
        $assets[0]->shouldReceive('getRelativePath')->once()->andReturn('foo.css');
        $assets[0]->shouldReceive('build')->once()->andReturn('body { background-color: #fff; }');
        $assets[1]->shouldReceive('isIgnored')->once()->andReturn(false);
        $assets[1]->shouldReceive('isRemote')->once()->andReturn(false);
        $assets[1]->shouldReceive('getRelativePath')->once()->andReturn('bar.css');
        $assets[1]->shouldReceive('build')->once()->andReturn('a { text-decoration: none; }');

        $expectedResponse = 'body { background-color: #fff; }'.PHP_EOL.'a { text-decoration: none; }';
        $fingerprint = md5($expectedResponse);

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('processCollection')->once();
        $collection->shouldReceive('getAssets')->once()->with('styles')->andReturn($assets);
        $collection->shouldReceive('getName')->once()->andReturn('foo');
        $collection->shouldReceive('determineExtension')->once()->andReturn('css');
        $config = $this->getConfigMock();
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->once()->with('path/to/build')->andReturn(false);
        $files->shouldReceive('makeDirectory')->once()->with('path/to/build');
        $files->shouldReceive('exists')->once()->with("path/to/build/foo-{$fingerprint}.css")->andReturn(false);
        $files->shouldReceive('put')->once()->with("path/to/build/foo-{$fingerprint}.css", $expectedResponse);

        $builder = new FilesystemBuilder($files, $config);

        $builder->setBuildPath('path/to/build');

        $builder->build($collection, 'styles');
    }

    /**
     * @expectedException Basset\Exception\CollectionExistsException
     */
    public function testFilesystemBuilderFailsWithNoChanges()
    {
        $assets = array($this->getAssetMock());
        $assets[0]->shouldReceive('isIgnored')->once()->andReturn(false);
        $assets[0]->shouldReceive('isRemote')->once()->andReturn(false);
        $assets[0]->shouldReceive('getRelativePath')->once()->andReturn('foo.css');
        $assets[0]->shouldReceive('build')->once()->andReturn('body { background-color: #fff; }');

        $fingerprint = md5('body { background-color: #fff; }');

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('processCollection')->once();
        $collection->shouldReceive('getAssets')->once()->with('styles')->andReturn($assets);
        $collection->shouldReceive('getName')->once()->andReturn('foo');
        $collection->shouldReceive('determineExtension')->once()->andReturn('css');
        $config = $this->getConfigMock();
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->once()->with('path/to/build')->andReturn(true);
        $files->shouldReceive('exists')->once()->with("path/to/build/foo-{$fingerprint}.css")->andReturn(true);

        $builder = new FilesystemBuilder($files, $config);

        $builder->setBuildPath('path/to/build');

        $builder->build($collection, 'styles');
    }


    public function testFilesystemBuilderWithForce()
    {
        $assets = array($this->getAssetMock());
        $assets[0]->shouldReceive('isIgnored')->once()->andReturn(false);
        $assets[0]->shouldReceive('isRemote')->once()->andReturn(false);
        $assets[0]->shouldReceive('getRelativePath')->once()->andReturn('foo.css');
        $assets[0]->shouldReceive('build')->once()->andReturn('body { background-color: #fff; }');

        $fingerprint = md5('body { background-color: #fff; }');

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('processCollection')->once();
        $collection->shouldReceive('getAssets')->once()->with('styles')->andReturn($assets);
        $collection->shouldReceive('getName')->once()->andReturn('foo');
        $collection->shouldReceive('determineExtension')->once()->andReturn('css');
        $config = $this->getConfigMock();
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->once()->with('path/to/build')->andReturn(true);
        $files->shouldReceive('exists')->once()->with("path/to/build/foo-{$fingerprint}.css")->andReturn(true);
        $files->shouldReceive('put')->once()->with("path/to/build/foo-{$fingerprint}.css", 'body { background-color: #fff; }');

        $builder = new FilesystemBuilder($files, $config);

        $builder->setBuildPath('path/to/build');

        $builder->force();

        $builder->build($collection, 'styles');
    }


    protected function getCollectionMock()
    {
        return m::mock('Basset\Collection');
    }


    protected function getAssetMock()
    {
        return m::mock('Basset\Asset');
    }


    protected function getConfigMock()
    {
        return m::mock('Illuminate\Config\Repository');
    }


    protected function getFilesMock()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }


}