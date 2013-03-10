<?php

use Mockery as m;
use Basset\AssetFinder;

class AssetFinderTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testFindRemotelyHostedAsset()
    {
        $finder = $this->getFinderInstance();

        $finder->getConfig()->shouldReceive('get')->once()->with('basset::aliases.assets.http://foo.bar/baz.css', 'http://foo.bar/baz.css')->andReturn('http://foo.bar/baz.css');

        $this->assertEquals('http://foo.bar/baz.css', $finder->find('http://foo.bar/baz.css'));
    }


    public function testFindPackageAsset()
    {
        $finder = $this->getFinderInstance();
        $finder->addNamespace('bar', 'foo/bar');

        $finder->getConfig()->shouldReceive('get')->once()->with('basset::aliases.assets.bar::baz.css', 'bar::baz.css')->andReturn('bar::baz.css');
        $finder->getFiles()->shouldReceive('exists')->once()->with('path/to/public/packages/foo/bar/baz.css')->andReturn(true);

        $this->assertEquals('path/to/public/packages/foo/bar/baz.css', $finder->find('bar::baz.css'));
    }


    public function testFindWorkingDirectoryAsset()
    {
        $finder = $this->getFinderInstance();

        $finder->getConfig()->shouldReceive('get')->once()->with('basset::aliases.assets.foo.css', 'foo.css')->andReturn('foo.css');

        $finder->getFiles()->shouldReceive('exists')->once()->with('path/to/public/working/directory')->andReturn(true);
        $finder->setWorkingDirectory('working/directory');

        $finder->getFiles()->shouldReceive('exists')->once()->with('path/to/public/working/directory/foo.css')->andReturn(true);

        $this->assertEquals('path/to/public/working/directory/foo.css', $finder->find('foo.css'));
    }


    public function testFindPublicPathAsset()
    {
        $finder = $this->getFinderInstance();

        $finder->getConfig()->shouldReceive('get')->once()->with('basset::aliases.assets.foo.css', 'foo.css')->andReturn('foo.css');
        $finder->getFiles()->shouldReceive('exists')->once()->with('path/to/public/foo.css')->andReturn(true);

        $this->assertEquals('path/to/public/foo.css', $finder->find('foo.css'));
    }


    /**
     * @expectedException Basset\Exception\DirectoryNotFoundException
     */
    public function testSettingInvalidWorkingDirectoryThrowsException()
    {
        $finder = $this->getFinderInstance();

        $finder->getFiles()->shouldReceive('exists')->once()->with('path/to/public/working/directory')->andReturn(false);
        $finder->setWorkingDirectory('working/directory');
    }


    public function testResettingWorkingDirectory()
    {
        $finder = $this->getFinderInstance();

        $finder->getFiles()->shouldReceive('exists')->once()->with('path/to/public/working/directory')->andReturn(true);
        $finder->setWorkingDirectory('working/directory');
        $this->assertEquals('path/to/public/working/directory', $finder->getWorkingDirectory());

        $finder->resetWorkingDirectory();
        $this->assertNull($finder->getWorkingDirectory());
    }


    protected function getFinderInstance()
    {
        return new AssetFinder($this->getFilesMock(), $this->getConfigMock(), 'path/to/public');
    }


    protected function getFilesMock()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }


    protected function getConfigMock()
    {
        return m::mock('Illuminate\Config\Repository');
    }


}