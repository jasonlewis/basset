<?php

use Mockery as m;

class FactoryTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}
	

	public function testCollectionInstanceIsCreated()
	{
		$files = m::mock('Illuminate\Filesystem\Filesystem');
		$config = m::mock('Illuminate\Config\Repository');
		$url = m::mock('Illuminate\Routing\UrlGenerator');
		$manager = m::mock('JasonLewis\Basset\AssetManager');
		$factory = new JasonLewis\Basset\Factory($files, $config, $url, $manager);
		$collection = $factory->collection('foo');
		$this->assertInstanceOf('JasonLewis\Basset\Collection', $collection);
	}


}