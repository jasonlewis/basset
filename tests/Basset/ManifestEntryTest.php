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
        $data = array('fingerprints' => array());
        $entry = new Entry($data);
        $this->assertEquals($data, $entry->toArray());
    }


    public function testSettingOfFingerprint()
    {
        $entry = new Entry;
        $entry->setFingerprint('foo', 'styles');
        $this->assertTrue($entry->hasFingerprint('styles'));
        $this->assertEquals('foo', $entry->getFingerprint('styles'));
    }


    public function testEntryCanBeConvertedToJson()
    {
        $data = array('fingerprints' => array());
        $entry = new Entry($data);
        $this->assertEquals(json_encode($data), $entry->toJson());
    }


}