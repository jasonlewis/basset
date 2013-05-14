<?php

use Mockery as m;

class AssetFactoryTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function setUp()
    {
        $this->files = m::mock('Illuminate\Filesystem\Filesystem');
        $this->filter = m::mock('Basset\Factory\FilterFactory');
        $this->factory = new Basset\Factory\AssetFactory($this->files, $this->filter, __DIR__, 'testing');
    }


    public function testMakeAsset()
    {
        $asset = $this->factory->make(__FILE__);

        $this->assertEquals(basename(__FILE__), $asset->getRelativePath());
        $this->assertEquals(__FILE__, $asset->getAbsolutePath());
    }


    public function testBuildingOfAbsolutePath()
    {
        $this->assertEquals(__FILE__, $this->factory->buildAbsolutePath(__FILE__));
        $this->assertEquals('http://foo.com', $this->factory->buildAbsolutePath('http://foo.com'));
        $this->assertEquals('//foo.com', $this->factory->buildAbsolutePath('//foo.com'));
    }

    public function testBuildingOfRelativePath()
    {
        $this->assertEquals('foo.css', $this->factory->buildRelativePath(__DIR__.'/foo.css'));
        $this->assertEquals('bar/foo.css', $this->factory->buildRelativePath(__DIR__.'/bar/foo.css'));
        $this->assertEquals('http://foo.com', $this->factory->buildRelativePath('http://foo.com'));
    }


    public function testBuildingOfRelativePathFromOutsidePublicDirectory()
    {
        $this->assertEquals(md5('path/to/outside').'/foo.css', $this->factory->buildRelativePath('path/to/outside/foo.css'));
        $this->assertEquals(md5('path/to').'/bar.css', $this->factory->buildRelativePath('path/to/bar.css'));
    }


}