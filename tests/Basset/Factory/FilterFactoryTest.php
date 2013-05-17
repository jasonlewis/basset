<?php

use Mockery as m;

class FilterFactoryTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function setUp()
    {
        $this->log = m::mock('Illuminate\Log\Writer');
        $this->factory = new Basset\Factory\FilterFactory($this->log, array('foo' => 'FooFilter', 'bar' => array('BarFilter', function($filter)
        {
            $filter->setArgument('foo');
        })), array(), 'testing');
    }


    public function testMakeNewFilterInstanceFromString()
    {
        $this->assertInstanceOf('Basset\Filter\Filter', $this->factory->make('FooFilter'));
    }


    public function testMakeFilterInstanceFromExistingFilterInstance()
    {
        $filter = m::mock('Basset\Filter\Filter');
        $this->assertEquals($filter, $this->factory->make($filter));
    }


    public function testMakeFromConfigAlias()
    {
        $filter = $this->factory->make('foo');
        $this->assertEquals('FooFilter', $filter->getFilter());
    }


    public function testMakeFromConfigAliasWithCallback()
    {
        $filter = $this->factory->make('bar');
        $this->assertContains('foo', $filter->getArguments());
    }


}