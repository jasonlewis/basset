<?php

use Mockery as m;
use Basset\Factory\Manager;

class ManagerTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testRegisterFactoryWithManager()
    {
        $manager = new Manager;

        $manager->register('foo', m::mock('Basset\Factory\FactoryInterface'));
        $this->assertArrayHasKey('foo', $manager->getFactories());

        $manager['bar'] = m::mock('Basset\Factory\FactoryInterface');
        $this->assertArrayHasKey('bar', $manager->getFactories());

        $manager->baz = m::mock('Basset\Factory\FactoryInterface');
        $this->assertArrayHasKey('baz', $manager->getFactories());
    }


    public function testGettingFactoryFromManager()
    {
        $manager = new Manager;

        $manager->register('foo', $mock = m::mock('Basset\Factory\FactoryInterface'));
        $this->assertEquals($mock, $manager->get('foo'));
        $this->assertEquals($mock, $manager['foo']);
        $this->assertEquals($mock, $manager->foo);
    }


    public function testCheckIfFactoryExists()
    {
        $manager = new Manager;

        $manager->register('foo', $mock = m::mock('Basset\Factory\FactoryInterface'));
        $this->assertTrue($manager->has('foo'));
        $this->assertTrue(isset($manager['foo']));
        $this->assertFalse($manager->has('bar'));
        $this->assertFalse(isset($manager['bar']));
    }


    public function testCountingOfFactories()
    {
        $manager = new Manager;

        $manager['foo'] = m::mock('Basset\Factory\FactoryInterface');
        $manager['bar'] = m::mock('Basset\Factory\FactoryInterface');
        $manager['baz'] = m::mock('Basset\Factory\FactoryInterface');

        $this->assertCount(3, $manager);
    }


}