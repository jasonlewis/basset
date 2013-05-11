<?php

use Mockery as m;
use Basset\Environment;

class EnvironmentTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testCollectionInstanceIsCreated()
    {
        $env = $this->getEnvInstance();

        $this->assertInstanceOf('Basset\Collection', $env->collection('foo'));

        $this->assertInstanceOf('Basset\Collection', $env->make('bar'));

        $env['baz'] = function(){};
        $this->assertInstanceOf('Basset\Collection', $env['baz']);
    }


    /**
     * @expectedException InvalidArgumentException
     */
    public function testOffsetSetWithNoCollectionNameThrowsException()
    {
        $env = $this->getEnvInstance();

        $env[] = function(){};
    }


    public function testRemovingCollectionFromEnvironment()
    {
        $env = $this->getEnvInstance();
        $env['baz'] = function(){};

        unset($env['baz']);

        $this->assertNull($env['baz']);
    }


    public function testCollectionCallbackIsFiredUponCreation()
    {
        $env = $this->getEnvInstance();

        $fired = false;

        $env->collection('foo', function($collection) use (&$fired)
        {
            $fired = true;
        });

        $this->assertTrue($fired);
    }


    public function testGetAllCollections()
    {
        $env = $this->getEnvInstance();

        $env->collection('foo');
        $env->collection('bar');

        $this->assertCount(2, $env->getCollections());
    }


    public function testCheckingCollectionExistence()
    {
        $env = $this->getEnvInstance();

        $env->collection('foo');

        $this->assertTrue($env->hasCollection('foo'));
        $this->assertTrue(isset($env['foo']));
        $this->assertFalse($env->hasCollection('bar'));
    }


    public function testAddingPackageNamespace()
    {
        $env = $this->getEnvInstance();

        $env->getFinder()->shouldReceive('addNamespace')->once()->with('bar', 'foo/bar');
        $env->getFinder()->shouldReceive('addNamespace')->once()->with('baz', 'foo/bar');

        $env->package('foo/bar');
        $env->package('foo/bar', 'baz');
    }


    public function testRegisteringArrayOfCollections()
    {
        $env = $this->getEnvInstance();

        $env->collections(array(
            'foo' => function(){},
            'bar' => function(){}
        ));

        $this->assertInstanceOf('Basset\Collection', $env['foo']);
        $this->assertInstanceOf('Basset\Collection', $env['bar']);
    }


    public function testCheckingIfRunningInProduction()
    {
        $env = $this->getEnvInstance();

        $env->getConfig()->shouldReceive('get')->once()->with('basset::production', array())->andReturn('production');
        $this->assertFalse($env->runningInProduction());

        $env->getConfig()->shouldReceive('get')->once()->with('basset::production', array())->andReturn('testing');
        $this->assertTrue($env->runningInProduction());
    }


    public function testGetFiles()
    {
        $env = $this->getEnvInstance();
        $this->assertInstanceOf('Illuminate\Filesystem\Filesystem', $env->getFiles());
    }


    public function testGetFactory()
    {
        $env = $this->getEnvInstance();
        $this->assertInstanceOf('Basset\Factory\Manager', $env->getFactory());
    }


    protected function getEnvInstance()
    {
        $files = m::mock('Illuminate\Filesystem\Filesystem');
        $config = m::mock('Illuminate\Config\Repository');
        $factory = m::mock('Basset\Factory\Manager');
        $finder = m::mock('Basset\AssetFinder');

        $finder->shouldReceive('setWorkingDirectory')->with('/')->andReturn('/');
        $factory->shouldReceive('offsetGet')->with('directory')->andReturn($directoryFactory = m::mock('Basset\Factory\DirectoryFactory'));
        $directoryFactory->shouldReceive('make')->with('/');

        return new Environment($files, $config, $factory, $finder, 'testing');
    }


}