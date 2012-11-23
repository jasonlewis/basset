<?php

use Mockery as m;

class DirectoryTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testCanApplyFiltersToDirectory()
	{
		$app = new Illuminate\Container;
		$app['files'] = m::mock('Illuminate\Filesystem');
		$app['config'] = new Illuminate\Config\Repository(m::mock('Illuminate\Config\LoaderInterface'), 'production');
		$app['config']->getLoader()->shouldReceive('load')->once()->with('production', 'basset', 'basset')->andReturn(array('filters' => array()));
		$directory = new Basset\Directory(__DIR__.'/fixtures', $app);
		$directory->requireDirectory();
		$directory->apply('FooFilter', array('option', 'option'));
		$pending = $directory->getPending();
		$this->assertArrayHasKey('FooFilter', $pending[0]->getFilters());
		$this->assertContains(array('option', 'option'), $pending[0]->getFilters());
	}


	public function testCanExcludeAssets()
	{
		$app = new Illuminate\Container;
		$app['files'] = m::mock('Illuminate\Filesystem');
		$app['config'] = new Illuminate\Config\Repository(m::mock('Illuminate\Config\LoaderInterface'), 'production');
		$directory = new Basset\Directory(__DIR__.'/fixtures', $app);
		$directory->requireDirectory()->except(array('sample.css', 'sample-exclude.css'));
		$this->assertEmpty($directory->getPending());
		$directory = new Basset\Directory(__DIR__.'/fixtures', $app);
		$directory->requireDirectory()->except(array('sample-exclude.css'));
		$pending = $directory->getPending();
		$asset = array_pop($pending);
		$this->assertEquals('sample.css', $asset->getName());
	}


	public function testCanOnlyShowSomeAssets()
	{
		$app = new Illuminate\Container;
		$app['files'] = m::mock('Illuminate\Filesystem');
		$app['config'] = new Illuminate\Config\Repository(m::mock('Illuminate\Config\LoaderInterface'), 'production');
		$directory = new Basset\Directory(__DIR__.'/fixtures', $app);
		$directory->requireDirectory()->only(array('sample.css'));
		$pending = $directory->getPending();
		$asset = array_pop($pending);
		$this->assertEquals('sample.css', $asset->getName());
	}


}