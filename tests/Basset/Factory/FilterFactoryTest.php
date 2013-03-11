<?php

use Mockery as m;
use Basset\Factory\FilterFactory;
use Illuminate\Config\Repository as Config;

class FilterFactoryTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testMakeNewFilterInstanceFromString()
    {
        $factory = new FilterFactory($config = $this->getConfigInstance());

        $config->getLoader()->shouldReceive('load')->once()->with('testing', 'aliases', 'basset')->andReturn(array());
        $config->getLoader()->shouldReceive('load')->once()->with('testing', 'node_paths', 'basset')->andReturn(array());

        $this->assertInstanceOf('Basset\Filter\Filter', $factory->make('FooFilter'));
    }


    public function testMakeFilterInstanceFromExistingInstance()
    {
        $factory = new FilterFactory($this->getConfigInstance());

        $filter = $this->getFilterMock();

        $this->assertEquals($filter, $factory->make($filter));
    }


    public function testMakeAliasedFilter()
    {
        $factory = new FilterFactory($config = $this->getConfigInstance());

        $config->getLoader()->shouldReceive('load')->once()->with('testing', 'aliases', 'basset')->andReturn(array(
            'filters' => array(
                'foo' => 'FooFilter'
            )
        ));
        $config->getLoader()->shouldReceive('load')->once()->with('testing', 'node_paths', 'basset')->andReturn(array());

        $filter = $factory->make('foo');

        $this->assertEquals('FooFilter', $filter->getFilter());
    }


    public function testMakedAliasedFilterWithCallback()
    {
        $factory = new FilterFactory($config = $this->getConfigInstance());

        $fired = false;
        $tester = $this;

        $config->getLoader()->shouldReceive('load')->once()->with('testing', 'aliases', 'basset')->andReturn(array(
            'filters' => array(
                'foo' => array(
                    'FooFilter' => function($filter) use (&$fired, $tester)
                    {
                        $fired = true;

                        $tester->assertEquals('FooFilter', $filter->getFilter());
                    }
                )
            )
        ));
        $config->getLoader()->shouldReceive('load')->once()->with('testing', 'node_paths', 'basset')->andReturn(array());

        $factory->make('foo');

        $this->assertTrue($fired);
    }


    protected function getConfigInstance()
    {
        return new Config(m::mock('Illuminate\Config\LoaderInterface'), 'testing');
    }


    protected function getFilterMock()
    {
        return m::mock('Basset\Filter\Filter');
    }


}