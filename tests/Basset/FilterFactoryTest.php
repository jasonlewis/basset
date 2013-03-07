<?php

use Mockery as m;
use Basset\Factory\FilterFactory;

class FilterFactoryTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testMakeNewFilterInstance()
    {
        $filterFactory = $this->getFilterFactoryInstance();
        $resource = $this->getResourceMock();

        $filter = $filterFactory->make('FooFilter', null, $resource);

        $this->assertInstanceOf('Basset\Filter\Filter', $filter);
    }


    public function testFilterClosureIsFired()
    {
        $filterFactory = $this->getFilterFactoryInstance();
        $resource = $this->getResourceMock();

        $fired = false;

        $filter = $filterFactory->make('FooFilter', function($filter) use (&$fired)
        {
            $fired = true;
        }, $resource);

        $this->assertTrue($fired);
    }


    public function testMakeExistingFilter()
    {
        $filterFactory = $this->getFilterFactoryInstance();
        $resource = $this->getResourceMock();

        $existingFilter = $this->getFilterMock();

        $filter = $filterFactory->make($existingFilter, null, $resource);

        $this->assertEquals($existingFilter, $filter);
    }


    public function testMakeNamedFilter()
    {
        $config = $this->getConfigMock();
        $config->shouldReceive('has')->once()->with('basset::filters.Foo')->andReturn(true);
        $config->shouldReceive('get')->once()->with('basset::filters.Foo')->andReturn('FooFilter');

        $filterFactory = new FilterFactory($config);
        $resource = $this->getResourceMock();

        $filter = $filterFactory->make('Foo', null, $resource);

        $this->assertEquals('FooFilter', $filter->getFilter());
    }


    public function testMakeNamedFilterWithCallback()
    {
        $fired = false;

        $config = $this->getConfigMock();
        $config->shouldReceive('has')->once()->with('basset::filters.Foo')->andReturn(true);
        $config->shouldReceive('get')->once()->with('basset::filters.Foo')->andReturn(array('FooFilter' => function($filter) use (&$fired)
        {
            $fired = true;
        }));

        $filterFactory = new FilterFactory($config);
        $resource = $this->getResourceMock();

        $filter = $filterFactory->make('Foo', null, $resource);

        $this->assertEquals('FooFilter', $filter->getFilter());
        $this->assertTrue($fired);
    }


    protected function getFilterFactoryInstance()
    {
        $config = $this->getConfigMock();
        $config->shouldReceive('has')->with('basset::filters.FooFilter')->andReturn(false);

        return new FilterFactory($config);
    }


    protected function getConfigMock()
    {
        return m::mock('Illuminate\Config\Repository');
    }


    protected function getFilterMock()
    {
        return m::mock('Basset\Filter\Filter');
    }


    protected function getResourceMock()
    {
        return m::mock('Basset\Filter\FilterableInterface');
    }


}