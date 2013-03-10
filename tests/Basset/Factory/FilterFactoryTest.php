<?php

use Mockery as m;
use Basset\Factory\FilterFactory;

class FilterFactoryTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testMakeNewFilterInstanceFromString()
    {
        $factory = new FilterFactory($config = $this->getConfigMock());

        $config->shouldReceive('get')->once()->with('basset::aliases.filters.FooFilter', 'FooFilter')->andReturn('FooFilter');

        $this->assertInstanceOf('Basset\Filter\Filter', $factory->make('FooFilter'));
    }


    public function testMakeFilterInstanceFromExistingInstance()
    {
        $factory = new FilterFactory($config = $this->getConfigMock());

        $filter = $this->getFilterMock();

        $this->assertEquals($filter, $factory->make($filter));
    }


    public function testMakeAliasedFilter()
    {
        $factory = new FilterFactory($config = $this->getConfigMock());

        $config->shouldReceive('get')->once()->with('basset::aliases.filters.foo', 'foo')->andReturn('FooFilter');

        $filter = $factory->make('foo');

        $this->assertEquals('FooFilter', $filter->getFilter());
    }


    public function testMakedAliasedFilterWithCallback()
    {
        $factory = new FilterFactory($config = $this->getConfigMock());

        $fired = false;
        $tester = $this;

        $config->shouldReceive('get')->once()->with('basset::aliases.filters.foo', 'foo')->andReturn(array(
            'FooFilter' => function($filter) use (&$fired, $tester)
            {
                $fired = true;

                $tester->assertEquals('FooFilter', $filter->getFilter());
            }
        ));

        $factory->make('foo');

        $this->assertTrue($fired);
    }


    protected function getConfigMock()
    {
        return m::mock('Illuminate\Config\Repository');
    }


    protected function getFilterMock()
    {
        return m::mock('Basset\Filter\Filter');
    }


}