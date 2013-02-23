<?php

use Mockery as m;

class BassetTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testCollectionInstanceIsCreated()
    {
        $basset = $this->getBassetInstance();

        $this->assertInstanceOf('JasonLewis\Basset\Collection', $basset->collection('foo'));
    }


    public function testGetAllCollections()
    {
        $basset = $this->getBassetInstance();

        $basset->collection('foo');
        $basset->collection('bar');

        $this->assertCount(2, $basset->getCollections());
    }


    public function testCheckingCollectionExistence()
    {
        $basset = $this->getBassetInstance();

        $basset->collection('foo');

        $this->assertTrue($basset->hasCollection('foo'));
        $this->assertFalse($basset->hasCollection('bar'));
    }


    protected function getBassetInstance()
    {
        $files = m::mock('Illuminate\Filesystem\Filesystem');
        $config = m::mock('Illuminate\Config\Repository');
        $assetFactory = m::mock('JasonLewis\Basset\AssetFactory');
        $filterFactory = m::mock('JasonLewis\Basset\FilterFactory');

        return new JasonLewis\Basset\Basset($files, $config, $assetFactory, $filterFactory);
    }


}