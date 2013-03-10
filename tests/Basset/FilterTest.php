<?php

use Mockery as m;
use Basset\Filter\Filter;

class FilterTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testSettingOfFilterInstantiationArguments()
    {
        $filter = $this->getFilterInstance();

        $filter->setArguments('bar', 'baz');

        $arguments = $filter->getArguments();

        $this->assertContains('bar', $arguments);
        $this->assertContains('baz', $arguments);
    }


    public function testSettingOfFilterEnvironments()
    {
        $filter = $this->getFilterInstance();

        $filter->onEnvironment('foo');
        $this->assertContains('foo', $filter->getEnvironments());

        $filter->onEnvironments('bar', 'baz');
        $this->assertContains('bar', $filter->getEnvironments());
        $this->assertContains('baz', $filter->getEnvironments());
    }


    public function testSettingOfFilterGroupRestriction()
    {
        $filter = $this->getFilterInstance();

        $filter->onlyJavascripts();
        $this->assertEquals('javascripts', $filter->getGroupRestriction());

        $filter->onlyStylesheets();
        $this->assertEquals('stylesheets', $filter->getGroupRestriction());
    }


    public function testInstantiationOfFiltersWithNoArguments()
    {
        $filter = $this->getFilterInstance();
        $filter->shouldReceive('exists')->once()->andReturn('FilterStub');

        $instance = $filter->instantiate();

        $this->assertInstanceOf('FilterStub', $instance);
    }


    public function testInstantiationOfFiltersWithArguments()
    {
        $filter = $this->getFilterInstance();
        $filter->shouldReceive('exists')->once()->andReturn('FilterWithConstructorStub');

        $filter->setArguments('bar');

        $instance = $filter->instantiate();

        $this->assertEquals('bar', $instance->getFoo());
    }


    public function testInstantiationOfFiltersWithBeforeFilteringCallback()
    {
        $filter = $this->getFilterInstance();
        $filter->shouldReceive('exists')->once()->andReturn('FilterStub');

        $tester = $this;

        $filter->beforeFiltering(function($filter) use ($tester)
        {
            $filter->setFoo('bar');

            $tester->assertInstanceOf('FilterStub', $filter);
        });

        $instance = $filter->instantiate();

        $this->assertEquals('bar', $instance->getFoo());
    }


    public function testInvalidMethodsAreHandledByResource()
    {
        $filter = new Filter('FooFilter');
        $filter->setResource($this->getResourceMock());
        $filter->getResource()->shouldReceive('foo')->once()->andReturn('bar');

        $this->assertEquals('bar', $filter->foo());
    }


    protected function getFilterInstance()
    {
        $mock = m::mock('Basset\Filter\Filter', array('FooFilter'))->shouldDeferMissing();
        $mock->setResource($this->getResourceMock());

        return $mock;
    }


    protected function getResourceMock()
    {
        return m::mock('Basset\Filter\FilterableInterface');
    }


}


class FilterStub {

    protected $foo;

    public function setFoo($foo)
    {
        $this->foo = $foo;
    }

    public function getFoo()
    {
        return $this->foo;
    }

}


class FilterWithConstructorStub {

    protected $foo;

    public function __construct($foo)
    {
        $this->foo = $foo;
    }

    public function getFoo()
    {
        return $this->foo;
    }

}