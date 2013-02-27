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
        $data = array('development' => array('foo' => 'bar'), 'fingerprints' => array());
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


    public function testAddingDevelopmentAssetToEntry()
    {
        $entry = new Entry;
        $entry->addDevelopment('path/to/foo.scss', 'path/to/foo.css', 'styles');
        $this->assertEquals(array('path/to/foo.scss' => 'path/to/foo.css'), $entry->getDevelopment('styles'));
    }


    public function testEntryCanBeConvertedToJson()
    {
        $data = array('development' => array('foo' => 'bar'), 'fingerprints' => array());
        $entry = new Entry($data);
        $this->assertEquals(json_encode($data), $entry->toJson());
    }


}