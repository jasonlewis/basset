<?php

use Mockery as m;
use JasonLewis\Basset\Compiler\FilesystemCompiler;

class FilesystemCompilerTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testFilesystemCompiler()
    {
        $assets = array(
            $this->getAssetMock(),
            $this->getAssetMock()
        );
        $assets[0]->shouldReceive('isIgnored')->once()->andReturn(false);
        $assets[0]->shouldReceive('isRemote')->once()->andReturn(false);
        $assets[0]->shouldReceive('getRelativePath')->once()->andReturn('foo.css');
        $assets[0]->shouldReceive('compile')->once()->andReturn('body { background-color: #fff; }');
        $assets[1]->shouldReceive('isIgnored')->once()->andReturn(false);
        $assets[1]->shouldReceive('isRemote')->once()->andReturn(false);
        $assets[1]->shouldReceive('getRelativePath')->once()->andReturn('bar.css');
        $assets[1]->shouldReceive('compile')->once()->andReturn('a { text-decoration: none; }');

        $expectedResponse = 'body { background-color: #fff; }'.PHP_EOL.'a { text-decoration: none; }';
        $fingerprint = md5($expectedResponse);

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('processCollection')->once();
        $collection->shouldReceive('getAssets')->once()->with('styles')->andReturn($assets);
        $collection->shouldReceive('getName')->once()->andReturn('foo');
        $config = $this->getConfigMock();
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->once()->with('path/to/compile')->andReturn(false);
        $files->shouldReceive('makeDirectory')->once()->with('path/to/compile');
        $files->shouldReceive('exists')->once()->with("path/to/compile/foo-{$fingerprint}.css")->andReturn(false);
        $files->shouldReceive('put')->once()->with("path/to/compile/foo-{$fingerprint}.css", $expectedResponse);

        $compiler = new FilesystemCompiler($files, $config);

        $compiler->setCompilePath('path/to/compile');

        $compiler->compile($collection, 'styles');
    }

    /**
     * @expectedException JasonLewis\Basset\Exceptions\CompilingNotRequiredException
     */
    public function testFilesystemCompilerFailsWithNoChanges()
    {
        $assets = array($this->getAssetMock());
        $assets[0]->shouldReceive('isIgnored')->once()->andReturn(false);
        $assets[0]->shouldReceive('isRemote')->once()->andReturn(false);
        $assets[0]->shouldReceive('getRelativePath')->once()->andReturn('foo.css');
        $assets[0]->shouldReceive('compile')->once()->andReturn('body { background-color: #fff; }');

        $fingerprint = md5('body { background-color: #fff; }');

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('processCollection')->once();
        $collection->shouldReceive('getAssets')->once()->with('styles')->andReturn($assets);
        $collection->shouldReceive('getName')->twice()->andReturn('foo');
        $config = $this->getConfigMock();
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->once()->with('path/to/compile')->andReturn(true);
        $files->shouldReceive('exists')->once()->with("path/to/compile/foo-{$fingerprint}.css")->andReturn(true);

        $compiler = new FilesystemCompiler($files, $config);

        $compiler->setCompilePath('path/to/compile');

        $compiler->compile($collection, 'styles');
    }


    public function testFilesystemCompilerWithForce()
    {
        $assets = array($this->getAssetMock());
        $assets[0]->shouldReceive('isIgnored')->once()->andReturn(false);
        $assets[0]->shouldReceive('isRemote')->once()->andReturn(false);
        $assets[0]->shouldReceive('getRelativePath')->once()->andReturn('foo.css');
        $assets[0]->shouldReceive('compile')->once()->andReturn('body { background-color: #fff; }');

        $fingerprint = md5('body { background-color: #fff; }');

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('processCollection')->once();
        $collection->shouldReceive('getAssets')->once()->with('styles')->andReturn($assets);
        $collection->shouldReceive('getName')->once()->andReturn('foo');
        $config = $this->getConfigMock();
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->once()->with('path/to/compile')->andReturn(true);
        $files->shouldReceive('exists')->once()->with("path/to/compile/foo-{$fingerprint}.css")->andReturn(true);
        $files->shouldReceive('put')->once()->with("path/to/compile/foo-{$fingerprint}.css", 'body { background-color: #fff; }');

        $compiler = new FilesystemCompiler($files, $config);

        $compiler->setCompilePath('path/to/compile');

        $compiler->force();

        $compiler->compile($collection, 'styles');
    }


    public function testFilesystemCompilerAsDevelopment()
    {
        $assets = array(
            $this->getAssetMock(),
            $this->getAssetMock()
        );
        $assets[0]->shouldReceive('isIgnored')->once()->andReturn(false);
        $assets[0]->shouldReceive('isRemote')->once()->andReturn(false);
        $assets[0]->shouldReceive('getRelativePath')->once()->andReturn('foo.css');
        $assets[0]->shouldReceive('compile')->once()->andReturn('body { background-color: #fff; }');
        $assets[1]->shouldReceive('isIgnored')->once()->andReturn(false);
        $assets[1]->shouldReceive('isRemote')->once()->andReturn(false);
        $assets[1]->shouldReceive('getRelativePath')->once()->andReturn('bar.css');
        $assets[1]->shouldReceive('compile')->once()->andReturn('a { text-decoration: none; }');

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('processCollection')->once();
        $collection->shouldReceive('getAssets')->once()->with('styles')->andReturn($assets);
        $collection->shouldReceive('getName')->once()->andReturn('foo');
        $config = $this->getConfigMock();
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->once()->with('path/to/compile/foo')->andReturn(false);
        $files->shouldReceive('makeDirectory')->once()->with('path/to/compile/foo');
        $files->shouldReceive('exists')->twice()->with("path/to/compile/foo")->andReturn(true);
        $files->shouldReceive('put')->once()->with("path/to/compile/foo/foo.css", 'body { background-color: #fff; }');
        $files->shouldReceive('put')->once()->with("path/to/compile/foo/bar.css", 'a { text-decoration: none; }');

        $compiler = new FilesystemCompiler($files, $config);

        $compiler->setCompilePath('path/to/compile');

        $compiler->compileDevelopment($collection, 'styles');
    }


    protected function getCollectionMock()
    {
        return m::mock('JasonLewis\Basset\Collection');
    }


    protected function getAssetMock()
    {
        return m::mock('JasonLewis\Basset\Asset');
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