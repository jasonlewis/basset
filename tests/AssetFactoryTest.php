<?php

use Mockery as m;

class AssetFactoryTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testMakeAsset()
    {
        $files = m::mock('Illuminate\Filesystem\Filesystem');
        $filterFactory = m::mock('JasonLewis\Basset\FilterFactory');
        $assetFactory = new JasonLewis\Basset\AssetFactory($files, $filterFactory, __DIR__, 'local');
        $asset = $assetFactory->make(__FILE__);
        $this->assertEquals(basename(__FILE__), $asset->getRelativePath());
        $this->assertEquals(__FILE__, $asset->getAbsolutePath());
    }


    public function testGetPathRelativeFromPublic()
    {
        $files = m::mock('Illuminate\Filesystem\Filesystem');
        $filterFactory = m::mock('JasonLewis\Basset\FilterFactory');
        $assetFactory = new JasonLewis\Basset\AssetFactory($files, $filterFactory, __DIR__, 'local');
        $this->assertEquals(__DIR__.'/foo.css', $assetFactory->path('foo.css'));
    }


    public function testGetAbsolutePath()
    {
        $files = m::mock('Illuminate\Filesystem\Filesystem');
        $filterFactory = m::mock('JasonLewis\Basset\FilterFactory');
        $assetFactory = new JasonLewis\Basset\AssetFactory($files, $filterFactory, __DIR__, 'local');
        $this->assertEquals(__FILE__, $assetFactory->getAbsolutePath(__FILE__));
        $this->assertEquals('http://foo.com', $assetFactory->getAbsolutePath('http://foo.com'));
    }


}