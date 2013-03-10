<?php

use Mockery as m;
use Basset\Factory\Manager;
use Basset\Factory\DirectoryFactory;
use Illuminate\Filesystem\Filesystem;

class DirectoryFactoryTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testMakingOfDirectory()
    {
        $factory = new DirectoryFactory(new Filesystem, new Manager);

        $directory = $factory->make('foo');

        $this->assertInstanceOf('Basset\Directory', $directory);
    }


}