<?php

use Mockery as m;

class AssetManagerTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testCanMakeAsset()
    {
        $files = m::mock('Illuminate\Filesystem\Filesystem');
        $manager = new JasonLewis\Basset\AssetManager($files, __DIR__, 'local');
        $asset = $manager->make(__FILE__);
        $this->assertInstanceOf('JasonLewis\Basset\Asset', $asset);
        $this->assertEquals(basename(__FILE__), $asset->getRelativePath());
        $this->assertEquals(__FILE__, $asset->getAbsolutePath());
    }


    public function testCanGetPathRelativeFromPublic()
    {
        $files = m::mock('Illuminate\Filesystem\Filesystem');
        $manager = new JasonLewis\Basset\AssetManager($files, __DIR__, 'local');
        $this->assertEquals(__DIR__.'/foo.css', $manager->path('foo.css'));
    }


    public function testCanGetAbsolutePath()
    {
        $files = m::mock('Illuminate\Filesystem\Filesystem');
        $manager = new JasonLewis\Basset\AssetManager($files, __DIR__, 'local');
        $this->assertEquals(__FILE__, $manager->getAbsolutePath(__FILE__));
        $this->assertEquals('http://foo.com', $manager->getAbsolutePath('http://foo.com'));
    }


}