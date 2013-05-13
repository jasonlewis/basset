<?php

use Mockery as m;
use Basset\Factory\DirectoryFactory;

class DirectoryFactoryTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testMakingOfDirectory()
    {
        $factory = new DirectoryFactory(m::mock('Basset\Factory\Manager'), m::mock('Basset\AssetFinder'));
        $directory = $factory->make('foo');
        $this->assertInstanceOf('Basset\Directory', $directory);
    }


}