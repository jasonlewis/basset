<?php

use Mockery as m;
use Basset\Environment;

class EnvironmentTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function setUp()
    {
        $this->files = m::mock('Illuminate\Filesystem\Filesystem');
        $this->config = m::mock('Illuminate\Config\Repository');
        $this->factory = m::mock('Basset\Factory\Manager');
        $this->finder = m::mock('Basset\AssetFinder');

        $this->finder->shouldReceive('setWorkingDirectory')->with('/')->andReturn('/');
        $this->factory->shouldReceive('offsetGet')->with('directory')->andReturn($directory = m::mock('Basset\Factory\DirectoryFactory'));
        $directory->shouldReceive('make')->with('/')->andReturn(m::mock('Basset\Directory'));

        $this->environment = new Environment($this->files, $this->config, $this->factory, $this->finder, 'testing');
    }


    public function testMakingNewCollectionReturnsNewCollectionInstance()
    {
        $this->assertInstanceOf('Basset\Collection', $this->environment->collection('foo'));
    }


    public function testMakingNewCollectionFiresCallback()
    {
        $fired = false;

        $this->environment->collection('foo', function() use (&$fired) { $fired = true; });
        $this->assertTrue($fired);
    }


    public function testRegisterPackageNamespaceAndVendorWithEnvironmentAndFinder()
    {
        $this->finder->shouldReceive('addNamespace')->once()->with('bar', 'foo/bar');
        $this->environment->package('foo/bar', 'bar');
    }


    public function testRegisteringArrayOfCollections()
    {
        $this->environment->collections(array(
            'foo' => function(){},
            'bar' => function(){}
        ));
        $this->assertCount(2, $this->environment->getCollections());
        $this->assertArrayHasKey('foo', $this->environment->getCollections()->all());
    }


}