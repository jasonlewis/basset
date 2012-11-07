<?php

use Mockery as m;
use Basset\Directory;

class DirectoryTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testCanApplyFiltersToDirectory()
	{
		$files = m::mock('Illuminate\Filesystem');
		$config = new Illuminate\Config\Repository(m::mock('Illuminate\Config\LoaderInterface'), 'production');

		$config->getLoader()->shouldReceive('load')->once()->with('production', 'basset', null)->andReturn(array(
			'filters' => array()
		));

		$directory = new Directory(__DIR__.'/fixtures', $files, $config);

		$directory->requireDirectory();
		$directory->apply('FooFilter', array('option', 'option'));

		$pending = $directory->getPending();

		$this->assertArrayHasKey('FooFilter', $pending[0]->getFilters());
		$this->assertContains(array('option', 'option'), $pending[0]->getFilters());
	}


	public function testCanExcludeAssets()
	{
		$files = m::mock('Illuminate\Filesystem');
		$config = new Illuminate\Config\Repository(m::mock('Illuminate\Config\LoaderInterface'), 'production');

		$directory = new Directory(__DIR__.'/fixtures', $files, $config);

		$directory->requireDirectory()->except(array('sample.css', 'sample-exclude.css'));

		$this->assertEmpty($directory->getPending());
	}


	public function testCanOnlyShowSomeAssets()
	{
		$files = m::mock('Illuminate\Filesystem');
		$config = new Illuminate\Config\Repository(m::mock('Illuminate\Config\LoaderInterface'), 'production');

		$directory = new Directory(__DIR__.'/fixtures', $files, $config);

		$directory->requireDirectory()->only(array('sample.css'));

		$pending = $directory->getPending();

		$asset = array_pop($pending);

		$this->assertEquals('sample.css', $asset->getName());
	}


}