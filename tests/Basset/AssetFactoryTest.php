<?php

use Mockery as m;
use JasonLewis\Basset\AssetFactory;

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


    public function testGetPathRelativeFromPublic()
    {
        $files = $this->getFilesMock();
        $filterFactory = $this->getFilterFactoryMock();
        $assetFactory = new AssetFactory($files, $filterFactory, __DIR__, 'testing');

        $this->assertEquals(__DIR__.'/foo.css', $assetFactory->path('foo.css'));
    }


    public function testGetAbsolutePath()
    {
        $files = $this->getFilesMock();
        $filterFactory = $this->getFilterFactoryMock();
        $assetFactory = new AssetFactory($files, $filterFactory, __DIR__, 'testing');

        $this->assertEquals(__FILE__, $assetFactory->getAbsolutePath(__FILE__));
        $this->assertEquals('http://foo.com', $assetFactory->getAbsolutePath('http://foo.com'));
    }

    protected function getFilesMock()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }


    protected function getFilterFactoryMock()
    {
        return m::mock('JasonLewis\Basset\FilterFactory');
    }


}