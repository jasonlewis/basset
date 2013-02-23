<?php

use Mockery as m;

class BassetTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testCollectionInstanceIsCreated()
    {
        $files = m::mock('Illuminate\Filesystem\Filesystem');
        $config = m::mock('Illuminate\Config\Repository');
        $assetFactory = m::mock('JasonLewis\Basset\AssetFactory');
        $filterFactory = m::mock('JasonLewis\Basset\FilterFactory');
        $basset = new JasonLewis\Basset\Basset($files, $config, $assetFactory, $filterFactory);
        $collection = $basset->collection('foo');
        $this->assertInstanceOf('JasonLewis\Basset\Collection', $collection);
    }


}