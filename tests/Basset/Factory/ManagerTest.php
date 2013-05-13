<?php

use Mockery as m;

class ManagerTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function setUp()
    {
        $this->manager = new Basset\Factory\Manager;
    }


    public function testRegisterFactoryWithManager()
    {
        $this->manager->register('foo', m::mock('Basset\Factory\FactoryInterface'));
        $this->assertArrayHasKey('foo', $this->manager->getFactories());

        $this->manager['bar'] = m::mock('Basset\Factory\FactoryInterface');
        $this->assertArrayHasKey('bar', $this->manager->getFactories());

        $this->manager->baz = m::mock('Basset\Factory\FactoryInterface');
        $this->assertArrayHasKey('baz', $this->manager->getFactories());
    }


    public function testGettingFactoryFromManager()
    {
        $this->manager->register('foo', $mock = m::mock('Basset\Factory\FactoryInterface'));
        $this->assertEquals($mock, $this->manager->get('foo'));
        $this->assertEquals($mock, $this->manager['foo']);
        $this->assertEquals($mock, $this->manager->foo);
    }


    public function testCheckIfFactoryExists()
    {
        $this->manager->register('foo', $mock = m::mock('Basset\Factory\FactoryInterface'));
        $this->assertTrue($this->manager->has('foo'));
        $this->assertTrue(isset($this->manager['foo']));
        $this->assertFalse($this->manager->has('bar'));
        $this->assertFalse(isset($this->manager['bar']));
    }


    public function testCountingOfFactories()
    {
        $this->manager['foo'] = m::mock('Basset\Factory\FactoryInterface');
        $this->manager['bar'] = m::mock('Basset\Factory\FactoryInterface');
        $this->manager['baz'] = m::mock('Basset\Factory\FactoryInterface');

        $this->assertCount(3, $this->manager);
    }


}