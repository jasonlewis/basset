<?php

use Mockery as m;
use JasonLewis\Basset\Filter;

class FilterTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testSettingOfFilterInstantiationArguments()
    {
        $resource = $this->getResourceMock();
        $filter = new Filter('FooFilter', $resource);

        $filter->setArguments('Foo', 'Bar');

        $this->assertContains('Foo', $filter->getArguments());
    }


    public function testSettingOfFilterEnvironments()
    {
        $resource = $this->getResourceMock();
        $filter = new Filter('FooFilter', $resource);

        $filter->onEnvironment('Foo');

        $this->assertContains('Foo', $filter->getEnvironments());

        $filter->onEnvironments('Bar', 'Baz');

        $this->assertContains('Baz', $filter->getEnvironments());
        $this->assertCount(3, $filter->getEnvironments());
    }


    public function testSettingOfFilterGroupRestriction()
    {
        $resource = $this->getResourceMock();
        $filter = new Filter('FooFilter', $resource);

        $filter->onlyStyles();

        $this->assertEquals('styles', $filter->getGroupRestriction());

        $filter->onlyScripts();

        $this->assertEquals('scripts', $filter->getGroupRestriction());
    }


    public function testFiltersCanBeInstantiated()
    {
        $resource = $this->getResourceMock();
        $filter = m::mock('JasonLewis\Basset\Filter[exists]', array('FooFilter', $resource));
        $filter->shouldReceive('exists')->once()->andReturn('stdClass');
        $filter->setArguments('Foo', 'Bar');

        $this->assertInstanceOf('stdClass', $filter->instantiate());
    }


    public function testInvalidMethodsCallsAreHandledByResource()
    {
        $resource = $this->getResourceMock();
        $resource->shouldReceive('foo')->once()->andReturn('bar');
        $filter = new Filter('FooFilter', $resource);

        $this->assertEquals('bar', $filter->foo());
    }


    protected function getResourceMock()
    {
        return m::mock('JasonLewis\Basset\FilterableInterface');
    }


}