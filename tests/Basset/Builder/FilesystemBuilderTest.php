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
        $builder = $this->getFilesystemBuilderInstance();

        $assets = array(
            $this->getAssetMock(),
            $this->getAssetMock()
        );
        $assets[0]->shouldReceive('isExcluded')->once()->andReturn(false);
        $assets[0]->shouldReceive('getRelativePath')->once()->andReturn('foo.css');
        $assets[0]->shouldReceive('build')->once()->andReturn('body { background-color: #fff; }');
        $assets[1]->shouldReceive('isExcluded')->once()->andReturn(false);
        $assets[1]->shouldReceive('getRelativePath')->once()->andReturn('bar.css');
        $assets[1]->shouldReceive('build')->once()->andReturn('a { text-decoration: none; }');

        $response = 'body { background-color: #fff; }'.PHP_EOL.'a { text-decoration: none; }';
        $fingerprint = md5($response);

        $builder->getFiles()->shouldReceive('exists')->once()->with('path/to/build')->andReturn(false);
        $builder->getFiles()->shouldReceive('makeDirectory')->once()->with('path/to/build');
        $builder->getFiles()->shouldReceive('exists')->once()->with("path/to/build/foo-{$fingerprint}.css")->andReturn(false);
        $builder->getFiles()->shouldReceive('put')->once()->with("path/to/build/foo-{$fingerprint}.css", $response);

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('determineExtension')->once()->andReturn('css');
        $collection->shouldReceive('getName')->once()->andReturn('foo');
        $collection->shouldReceive('getAssets')->once()->andReturn($assets);

        $builder->setBuildPath('path/to/build');

        $builder->build($collection, 'stylesheets');
    }


    /**
     * @expectedException Basset\Exception\CollectionExistsException
     */
    public function testFilesystemBuilderThrowsExceptionWhenNoChangesDetected()
    {
        $builder = $this->getFilesystemBuilderInstance();

        $assets = array($this->getAssetMock());
        $assets[0]->shouldReceive('isExcluded')->once()->andReturn(false);
        $assets[0]->shouldReceive('getRelativePath')->once()->andReturn('foo.css');
        $assets[0]->shouldReceive('build')->once()->andReturn($response = 'body { background-color: #fff; }');

        $fingerprint = md5($response);

        $builder->getFiles()->shouldReceive('exists')->once()->with('path/to/build')->andReturn(true);
        $builder->getFiles()->shouldReceive('exists')->once()->with("path/to/build/foo-{$fingerprint}.css")->andReturn(true);

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('determineExtension')->once()->andReturn('css');
        $collection->shouldReceive('getName')->twice()->andReturn('foo');
        $collection->shouldReceive('getAssets')->once()->andReturn($assets);

        $builder->setBuildPath('path/to/build');

        $builder->build($collection, 'stylesheets');
    }


    public function testFilesystemBuilderDoesNotThrowExceptionWhenForcingTheBuild()
    {
        $builder = $this->getFilesystemBuilderInstance();

        $assets = array($this->getAssetMock());
        $assets[0]->shouldReceive('isExcluded')->once()->andReturn(false);
        $assets[0]->shouldReceive('getRelativePath')->once()->andReturn('foo.css');
        $assets[0]->shouldReceive('build')->once()->andReturn($response = 'body { background-color: #fff; }');

        $fingerprint = md5($response);

        $builder->getFiles()->shouldReceive('exists')->once()->with('path/to/build')->andReturn(true);
        $builder->getFiles()->shouldReceive('exists')->once()->with("path/to/build/foo-{$fingerprint}.css")->andReturn(true);
        $builder->getFiles()->shouldReceive('put')->once()->with("path/to/build/foo-{$fingerprint}.css", $response);

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('determineExtension')->once()->andReturn('css');
        $collection->shouldReceive('getName')->once()->andReturn('foo');
        $collection->shouldReceive('getAssets')->once()->andReturn($assets);

        $builder->setBuildPath('path/to/build');

        $builder->force();

        $builder->build($collection, 'stylesheets');
    }


    public function testDevelopmentCollectionsAreBuiltWithFilesystemBuilder()
    {
        $builder = $this->getFilesystemBuilderInstance();

        $assets = array(
            $this->getAssetMock(),
            $this->getAssetMock()
        );
        $assets[0]->shouldReceive('isExcluded')->once()->andReturn(false);
        $assets[0]->shouldReceive('getRelativePath')->once()->andReturn('foo.css');
        $assets[0]->shouldReceive('build')->once()->andReturn('body { background-color: #fff; }');
        $assets[1]->shouldReceive('isExcluded')->once()->andReturn(false);
        $assets[1]->shouldReceive('getRelativePath')->once()->andReturn('bar/baz.css');
        $assets[1]->shouldReceive('build')->once()->andReturn('a { text-decoration: none; }');

        $builder->getFiles()->shouldReceive('exists')->once()->with('path/to/build')->andReturn(false);
        $builder->getFiles()->shouldReceive('exists')->twice()->with('path/to/build/foo')->andReturn(true);
        $builder->getFiles()->shouldReceive('exists')->once()->with('path/to/build/foo/bar')->andReturn(true);
        $builder->getFiles()->shouldReceive('makeDirectory')->once()->with('path/to/build');
        $builder->getFiles()->shouldReceive('put')->once()->with("path/to/build/foo/foo.css", 'body { background-color: #fff; }');
        $builder->getFiles()->shouldReceive('put')->once()->with("path/to/build/foo/bar/baz.css", 'a { text-decoration: none; }');

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('determineExtension')->once()->andReturn('css');
        $collection->shouldReceive('getName')->once()->andReturn('foo');
        $collection->shouldReceive('getAssets')->once()->andReturn($assets);

        $builder->setBuildPath('path/to/build');

        $builder->build($collection, 'stylesheets', true);
    }


    protected function getFilesystemBuilderInstance()
    {
        return new FilesystemBuilder($this->getFilesMock());
    }


    protected function getCollectionMock()
    {
        return m::mock('Basset\Collection');
    }


    protected function getAssetMock()
    {
        return m::mock('Basset\Asset');
    }


    protected function getFilesMock()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }


}