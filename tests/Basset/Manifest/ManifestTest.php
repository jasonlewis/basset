<?php

use Mockery as m;
use Basset\Manifest\Manifest;

class ManifestTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testSettingAndGettingOfEntryOnManifest()
    {
        $manifest = new Manifest;
        $manifest->setEntry('foo', $this->getEntryMock());

        $this->assertTrue($manifest->hasEntry('foo'));
        $this->assertInstanceOf('Basset\Manifest\Entry', $manifest->getEntry('foo'));
    }


    public function testManifestCanBeConvertedToArray()
    {
        $manifest = new Manifest;
        $manifest->setEntry('foo', $mock = $this->getEntryMock());

        $mock->shouldReceive('toArray')->once()->andReturn(array('bar' => 'baz'));
        $this->assertEquals(array('foo' => array('bar' => 'baz')), $manifest->toArray());
    }


    public function testManifestCanBeConvertedToJson()
    {
        $manifest = new Manifest;
        $manifest->setEntry('foo', $mock = $this->getEntryMock());

        $mock->shouldReceive('toArray')->once()->andReturn(array('bar' => 'baz'));
        $this->assertEquals(json_encode(array('foo' => array('bar' => 'baz'))), $manifest->toJson());
    }


    protected function getEntryMock()
    {
        return m::mock('Basset\Manifest\Entry');
    }


}