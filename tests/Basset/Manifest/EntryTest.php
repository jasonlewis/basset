<?php

use Mockery as m;
use Basset\Manifest\Entry;

class ManifestEntryTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testDefaultArrayIsParsedCorrectly()
    {
        $data = array('fingerprints' => array(), 'development' => array());
        $entry = new Entry($data);
        $this->assertEquals($data, $entry->toArray());
    }


    public function testSettingOfFingerprint()
    {
        $entry = new Entry;
        $entry->setFingerprint('foo', 'stylesheets');
        $this->assertTrue($entry->hasFingerprint('stylesheets'));
        $this->assertEquals('foo', $entry->getFingerprint('stylesheets'));
    }


    public function testEntryCanBeConvertedToJson()
    {
        $data = array('fingerprints' => array(), 'development' => array());
        $entry = new Entry($data);
        $this->assertEquals(json_encode($data), $entry->toJson());
    }


}