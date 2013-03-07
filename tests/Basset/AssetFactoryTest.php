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
        $filterFactory = $this->getFilterFactoryMock();
        $assetFactory = new AssetFactory($files, $filterFactory, __DIR__, 'testing');

        $asset = $assetFactory->make(__FILE__);

        $this->assertEquals(basename(__FILE__), $asset->getRelativePath());
        $this->assertEquals(__FILE__, $asset->getAbsolutePath());
    }


    public function testBuildingOfAbsolutePath()
    {
        $files = $this->getFilesMock();
        $filterFactory = $this->getFilterFactoryMock();
        $assetFactory = new AssetFactory($files, $filterFactory, '/path/to/public', 'testing');

        $this->assertEquals(__FILE__, $assetFactory->buildAbsolutePath(__FILE__));
        $this->assertEquals('http://foo.com', $assetFactory->buildAbsolutePath('http://foo.com'));
    }


    public function testBuildingOfRelativePath()
    {
        $files = $this->getFilesMock();
        $filterFactory = $this->getFilterFactoryMock();
        $assetFactory = new AssetFactory($files, $filterFactory, __DIR__, 'testing');

        $this->assertEquals('foo.css', $assetFactory->buildRelativePath(__DIR__.'/foo.css'));
        $this->assertEquals('bar/foo.css', $assetFactory->buildRelativePath(__DIR__.'/bar/foo.css'));
        $this->assertEquals('http://foo.com', $assetFactory->buildRelativePath('http://foo.com'));
    }


    public function testBuildingOfRelativePathFromOutsidePublicDirectory()
    {
        $files = $this->getFilesMock();
        $filterFactory = $this->getFilterFactoryMock();
        $assetFactory = new AssetFactory($files, $filterFactory, '/path/to/public', 'testing');

        $this->assertEquals(md5('path/to/outside').'/foo.css', $assetFactory->buildRelativePath('path/to/outside/foo.css'));
        $this->assertEquals(md5('path/to').'/bar.css', $assetFactory->buildRelativePath('path/to/bar.css'));
    }


    protected function getFilesMock()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }


    protected function getFilterFactoryMock()
    {
        return m::mock('Basset\Factory\FilterFactory');
    }


}