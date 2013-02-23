<?php

use Mockery as m;

class FilterTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testSettingOfFilterInstantiationArguments()
    {
        $resource = m::mock('JasonLewis\Basset\FilterableInterface');
        $filter = new JasonLewis\Basset\Filter('FooFilter', $resource);
        $filter->setArguments('Foo', 'Bar');
        $arguments = $filter->getArguments();
        $this->assertEquals('Foo', $arguments[0]);
    }


    public function testSettingOfFilterEnvironments()
    {
        $resource = m::mock('JasonLewis\Basset\FilterableInterface');
        $filter = new JasonLewis\Basset\Filter('FooFilter', $resource);
        $filter->onEnvironment('Foo');
        $environments = $filter->getEnvironments();
        $this->assertEquals('Foo', $environments[0]);
        $filter->onEnvironments('Bar', 'Zoo');
        $environments = $filter->getEnvironments();
        $this->assertEquals('Bar', $environments[1]);
    }


    public function testSettingOfFilterGroupRestriction()
    {
        $resource = m::mock('JasonLewis\Basset\FilterableInterface');
        $filter = new JasonLewis\Basset\Filter('FooFilter', $resource);
        $this->assertEquals(null, $filter->getGroupRestriction());
        $filter->onlyStyles();
        $this->assertEquals('styles', $filter->getGroupRestriction());
        $filter->onlyScripts();
        $this->assertEquals('scripts', $filter->getGroupRestriction());
    }


    public function testFiltersCanBeInstantiated()
    {
        $resource = m::mock('JasonLewis\Basset\FilterableInterface');
        $filter = m::mock('JasonLewis\Basset\Filter[exists]', array('FooFilter', $resource));
        $filter->shouldReceive('exists')->once()->andReturn('stdClass');
        $filter->setArguments('Foo', 'Bar');
        $this->assertInstanceOf('stdClass', $filter->instantiate());
    }


    public function testInvalidMethodsCallsAreHandledByResource()
    {
        $resource = m::mock('JasonLewis\Basset\FilterableInterface');
        $resource->shouldReceive('fooBar')->once()->andReturn('barFoo');
        $filter = new JasonLewis\Basset\Filter('FooFilter', $resource);
        $this->assertEquals('barFoo', $filter->fooBar());
    }


}