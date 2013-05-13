<?php

use Mockery as m;
use Basset\Asset;
use Basset\Collection;
use Illuminate\Support\Collection as IlluminateCollection;

class CollectionTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function setUp()
    {
        $this->collection = new Collection('foo', $this->directory = m::mock('Basset\Directory'));
    }


    public function testGetNameOfCollection()
    {
        $this->assertEquals('foo', $this->collection->getName());
    }


    public function testGetDefaultDirectory()
    {
        $this->assertEquals($this->directory, $this->collection->getDefaultDirectory());
    }


    public function testGetExtensionFromGroup()
    {
        $this->assertEquals('css', $this->collection->getExtension('stylesheets'));
        $this->assertEquals('js', $this->collection->getExtension('javascripts'));
    }


    public function testGettingCollectionAssetsWithDefaultOrdering()
    {
        $this->directory->shouldReceive('getAssets')->andReturn($expected = new IlluminateCollection(array(
            $this->newAsset('bar.css', 'path/to/bar.css', 1),
            $this->newAsset('baz.css', 'path/to/baz.css', 2)
        )));

        $this->assertEquals($expected->all(), $this->collection->getAssets('stylesheets')->all());
    }


    public function testGettingCollectionWithMultipleAssetGroupsReturnsOnlyRequestedGroup()
    {
        $this->directory->shouldReceive('getAssets')->andReturn(new IlluminateCollection(array(
            $assets[] = $this->newAsset('foo.css', 'path/to/foo.css', 1),
            $assets[] = $this->newAsset('bar.js', 'path/to/bar.js', 2),
            $assets[] = $this->newAsset('baz.js', 'path/to/baz.js', 3),
            $assets[] = $this->newAsset('qux.css', 'path/to/qux.css', 4)
        )));

        $expected = array(0 => $assets[0], 3 => $assets[3]);
        $this->assertEquals($expected, $this->collection->getAssets('stylesheets')->all());
    }


    public function testGettingCollectionAssetsWithCustomOrdering()
    {
        $this->directory->shouldReceive('getAssets')->andReturn(new IlluminateCollection(array(
            $assets[] = $this->newAsset('foo.css', 'path/to/foo.css', 1), // Becomes 2nd
            $assets[] = $this->newAsset('bar.css', 'path/to/bar.css', 2), // Becomes 4th
            $assets[] = $this->newAsset('baz.css', 'path/to/baz.css', 1), // Becomes 1st
            $assets[] = $this->newAsset('qux.css', 'path/to/qux.css', 4), // Becomes 5th
            $assets[] = $this->newAsset('zin.css', 'path/to/zin.css', 3)  // Becomes 3rd
        )));

        $expected = array($assets[2], $assets[0], $assets[4], $assets[1], $assets[3]);
        $this->assertEquals($expected, $this->collection->getAssets('stylesheets')->all());
    }


    public function testGettingCollectionExcludedAssets()
    {
        $this->directory->shouldReceive('getAssets')->andReturn(new IlluminateCollection(array(
            $assets[] = $this->newAsset('foo.css', 'path/to/foo.css', 1),
            $assets[] = $this->newAsset('bar.css', 'path/to/bar.css', 2)
        )));

        $assets[1]->exclude();

        $this->assertEquals(array(1 => $assets[1]), $this->collection->getExcludedAssets('stylesheets')->all());
    }


    public function newAsset($relative, $absolute, $order)
    {
        return new Asset(m::mock('Illuminate\Filesystem\Filesystem'), m::mock('Basset\Factory\Manager'), $absolute, $relative, 'testing', $order);
    }


}