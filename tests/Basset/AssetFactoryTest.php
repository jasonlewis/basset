<?php

use Mockery as m;
use Basset\Factory\AssetFactory;

class AssetFactoryTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testMakeAsset()
    {
        $files = $this->getFilesMock();
        $manager = $this->getFactoryManagerMock();
        $factory = new AssetFactory($files, $manager, __DIR__, 'testing');

        $asset = $factory->make(__FILE__);

        $this->assertEquals(basename(__FILE__), $asset->getRelativePath());
        $this->assertEquals(__FILE__, $asset->getAbsolutePath());
        $this->assertInstanceOf('Basset\Asset', $asset);
    }


    public function testBuildingOfAbsolutePath()
    {
        $files = $this->getFilesMock();
        $manager = $this->getFactoryManagerMock();
        $factory = new AssetFactory($files, $manager, __DIR__, 'testing');

        $this->assertEquals(__FILE__, $factory->buildAbsolutePath(__FILE__));
        $this->assertEquals('http://foo.com', $factory->buildAbsolutePath('http://foo.com'));
    }


    public function testBuildingOfRelativePath()
    {
        $files = $this->getFilesMock();
        $manager = $this->getFactoryManagerMock();
        $factory = new AssetFactory($files, $manager, __DIR__, 'testing');

        $this->assertEquals('foo.css', $factory->buildRelativePath(__DIR__.'/foo.css'));
        $this->assertEquals('bar/foo.css', $factory->buildRelativePath(__DIR__.'/bar/foo.css'));
        $this->assertEquals('http://foo.com', $factory->buildRelativePath('http://foo.com'));
    }


    public function testBuildingOfRelativePathFromOutsidePublicDirectory()
    {
        $files = $this->getFilesMock();
        $manager = $this->getFactoryManagerMock();
        $factory = new AssetFactory($files, $manager, __DIR__, 'testing');

        $this->assertEquals(md5('path/to/outside').'/foo.css', $factory->buildRelativePath('path/to/outside/foo.css'));
        $this->assertEquals(md5('path/to').'/bar.css', $factory->buildRelativePath('path/to/bar.css'));
    }


    protected function getFilesMock()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }


    protected function getFactoryManagerMock()
    {
        return m::mock('Basset\Factory\FactoryManager');
    }


}