<?php

use Mockery as m;

class FilterFactoryTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function setUp()
    {
        $this->config = m::mock('Illuminate\Config\Repository');
        $this->factory = new Basset\Factory\FilterFactory($this->config);
    }


    public function testMakeNewFilterInstanceFromString()
    {
        $this->config->shouldReceive('get')->once()->with('basset::aliases.filters.FooFilter', 'FooFilter')->andReturn('FooFilter');
        $this->config->shouldReceive('get')->once()->with('basset::node_paths')->andReturn(array());
        $this->assertInstanceOf('Basset\Filter\Filter', $this->factory->make('FooFilter'));
    }


    public function testMakeFilterInstanceFromExistingFilterInstance()
    {
        $filter = m::mock('Basset\Filter\Filter');
        $this->assertEquals($filter, $this->factory->make($filter));
    }


    public function testMakeFromConfigAlias()
    {
        $this->config->shouldReceive('get')->once()->with('basset::aliases.filters.foo', 'foo')->andReturn('FooFilter');
        $this->config->shouldReceive('get')->once()->with('basset::node_paths')->andReturn(array());
        $filter = $this->factory->make('foo');
        $this->assertEquals('FooFilter', $filter->getFilter());
    }


    public function testMakeFromConfigAliasWithCallback()
    {
        $filter = null;
        $this->config->shouldReceive('get')->once()->with('basset::aliases.filters.foo', 'foo')->andReturn(array('FooFilter', function($f) use (&$filter)
        {
            $filter = $f;
            $fired = true;
        }));
        $this->config->shouldReceive('get')->once()->with('basset::node_paths')->andReturn(array());

        $this->factory->make('foo');
        $this->assertInstanceOf('Basset\Filter\Filter', $filter);
    }


}