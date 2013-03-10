<?php

use Mockery as m;
use Basset\AssetFinder;
use Illuminate\Config\Repository as Config;

class AssetFinderTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testFindRemotelyHostedAsset()
    {
        $finder = $this->getFinderInstance();

        $finder->getConfig()->getLoader()->shouldReceive('load')->once()->with('testing', 'aliases', 'basset')->andReturn(array());

        $this->assertEquals('http://foo.bar/baz.css', $finder->find('http://foo.bar/baz.css'));
    }


    public function testFindPackageAsset()
    {
        $finder = $this->getFinderInstance();
        $finder->addNamespace('bar', 'foo/bar');

        $finder->getConfig()->getLoader()->shouldReceive('load')->once()->with('testing', 'aliases', 'basset')->andReturn(array());
        $finder->getFiles()->shouldReceive('exists')->once()->with('path/to/public/packages/foo/bar/baz.css')->andReturn(true);

        $this->assertEquals('path/to/public/packages/foo/bar/baz.css', $finder->find('bar::baz.css'));
    }


    public function testFindWorkingDirectoryAsset()
    {
        $finder = $this->getFinderInstance();

        $finder->getConfig()->getLoader()->shouldReceive('load')->once()->with('testing', 'aliases', 'basset')->andReturn(array());

        $finder->getFiles()->shouldReceive('exists')->once()->with('path/to/public/working/directory')->andReturn(true);
        $finder->setWorkingDirectory('working/directory');

        $finder->getFiles()->shouldReceive('exists')->once()->with('path/to/public/working/directory/foo.css')->andReturn(true);

        $this->assertEquals('path/to/public/working/directory/foo.css', $finder->find('foo.css'));
    }


    public function testFindPublicPathAsset()
    {
        $finder = $this->getFinderInstance();

        $finder->getConfig()->getLoader()->shouldReceive('load')->once()->with('testing', 'aliases', 'basset')->andReturn(array());
        $finder->getFiles()->shouldReceive('exists')->once()->with('path/to/public/foo.css')->andReturn(true);

        $this->assertEquals('path/to/public/foo.css', $finder->find('foo.css'));
    }


    public function testFindAliasedAsset()
    {
        $finder = $this->getFinderInstance();

        $finder->getConfig()->getLoader()->shouldReceive('load')->once()->with('testing', 'aliases', 'basset')->andReturn(array(
            'assets' => array(
                'foo' => 'foo.css'
            )
        ));
        $finder->getFiles()->shouldReceive('exists')->once()->with('path/to/public/foo.css')->andReturn(true);

        $this->assertEquals('path/to/public/foo.css', $finder->find('foo'));
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
        $this->assertFalse($finder->getWorkingDirectory());
    }


    public function testWorkingDirectoryStackIsPrefixed()
    {
        $finder = $this->getFinderInstance();

        $finder->getFiles()->shouldReceive('exists')->once()->with('path/to/public/working/directory')->andReturn(true);
        $finder->getFiles()->shouldReceive('exists')->once()->with('path/to/public/working/directory/foo/bar/baz')->andReturn(true);

        $finder->setWorkingDirectory('working/directory');
        $this->assertEquals('path/to/public/working/directory', $finder->getWorkingDirectory());

        $finder->setWorkingDirectory('foo/bar/baz');
        $this->assertEquals('path/to/public/working/directory/foo/bar/baz', $finder->getWorkingDirectory());

        $finder->resetWorkingDirectory();
        $this->assertEquals('path/to/public/working/directory', $finder->getWorkingDirectory());

        $finder->resetWorkingDirectory();
        $this->assertFalse($finder->getWorkingDirectory());
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
        return new Config(m::mock('Illuminate\Config\LoaderInterface'), 'testing');
    }


}