<?php

use Mockery as m;

class BuilderTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function setUp()
	{
		$this->files = m::mock('Illuminate\Filesystem\Filesystem');
		$this->files->shouldReceive('exists')->once()->with('foo')->andReturn(true);
		$this->manifest = m::mock('Basset\Manifest\Repository');
		$this->builder = new Basset\Builder\Builder($this->files, $this->manifest, 'foo');
	}


	public function testBuilderChecksForBuildPathAndMakesDirectoryIfItDoesNotExist()
	{
		$this->files->shouldReceive('exists')->once()->with('foo')->andReturn(false);
		$this->files->shouldReceive('makeDirectory')->once()->with('foo')->andReturn(true);

		$builder = new Basset\Builder\Builder($this->files, $this->manifest, 'foo');
	}


	/**
	 * @expectedException Basset\Exceptions\BuildNotRequiredException
	 */
	public function testBuildingEmptyProductionCollectionThrowsBuildNotRequiredException()
	{
		$collection = m::mock('Basset\Collection');
		$collection->shouldReceive('getAssetsWithoutExcluded')->once()->with('stylesheets')->andReturn(new Illuminate\Support\Collection);
		$collection->shouldReceive('getName')->once()->andReturn('foo');

		$this->manifest->shouldReceive('make')->once()->with('foo')->andReturn($entry = m::mock('Basset\Manifest\Entry'));
		$entry->shouldReceive('resetProductionFingerprint')->once()->with('stylesheets');

		$this->builder->buildAsProduction($collection, 'stylesheets');
	}


	/**
	 * @expectedException Basset\Exceptions\BuildNotRequiredException
	 */
	public function testBuildingExistingProductionCollectionThrowsBuildNotRequiredException()
	{
		$collection = m::mock('Basset\Collection');
		$collection->shouldReceive('getAssetsWithoutExcluded')->once()->with('stylesheets')->andReturn(new Illuminate\Support\Collection(array(
			$asset = m::mock('Basset\Asset')
		)));
		$asset->shouldReceive('build')->once()->andReturn('body { }');

		$collection->shouldReceive('getName')->once()->andReturn('foo');
		$collection->shouldReceive('getExtension')->once()->with('stylesheets')->andReturn('css');

		$this->manifest->shouldReceive('make')->once()->with('foo')->andReturn($entry = m::mock('Basset\Manifest\Entry'));
		$entry->shouldReceive('getProductionFingerprint')->with('stylesheets')->andReturn($fingerprint = 'foo-'.md5('body { }').'.css');

		$this->files->shouldReceive('exists')->once()->with('foo/'.$fingerprint)->andReturn(true);

		$this->builder->buildAsProduction($collection, 'stylesheets');
	}


	public function testBuildingProductionCollectionWritesToFilesystemAndSetsProductionFingerprint()
	{
		$collection = m::mock('Basset\Collection');
		$collection->shouldReceive('getAssetsWithoutExcluded')->once()->with('stylesheets')->andReturn(new Illuminate\Support\Collection(array(
			$asset = m::mock('Basset\Asset')
		)));
		$asset->shouldReceive('build')->once()->andReturn('body { }');

		$collection->shouldReceive('getName')->once()->andReturn('foo');
		$collection->shouldReceive('getExtension')->once()->with('stylesheets')->andReturn('css');

		$fingerprint = 'foo-'.md5('body { }').'.css';
		
		$this->manifest->shouldReceive('make')->once()->with('foo')->andReturn($entry = m::mock('Basset\Manifest\Entry'));
		$entry->shouldReceive('getProductionFingerprint')->with('stylesheets')->andReturn(null);
		$entry->shouldReceive('setProductionFingerprint')->with('stylesheets', $fingerprint);

		$this->files->shouldReceive('put')->once()->with('foo/'.$fingerprint, 'body { }');

		$this->builder->buildAsProduction($collection, 'stylesheets');
	}


}