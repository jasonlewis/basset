<?php

use Mockery as m;
use Basset\Basset;

class BassetTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testShowCollectionInDevelopment()
	{
		$app = $this->getApplication();
		$app['config']->shouldReceive('get')->once()->with('basset::collections')->andReturn(array());
		$app['files']->shouldReceive('exists')->once()->with('path/to/public/sample.css')->andReturn(true);
		$app['files']->shouldReceive('getRemote')->once()->andReturn('html { background-color: #fff; }');
		$app['files']->shouldReceive('extension')->once()->andReturn('css');
		$app['files']->shouldReceive('lastModified')->once()->andReturn($lastModified = time());
		$app['files']->shouldReceive('exists')->once()->with('path/to/public/nested/sample.css')->andReturn(true);
		$app['files']->shouldReceive('getRemote')->once()->andReturn('html { background-color: #fff; }');
		$app['files']->shouldReceive('extension')->once()->andReturn('css');
		$app['files']->shouldReceive('lastModified')->once()->andReturn($lastModified);
		$app['env'] = 'development';
		$app['config']->shouldReceive('get')->once()->with('basset::production_environment')->andReturn('production');
		$app['config']->shouldReceive('get')->once()->with('basset::compiling_path')->andReturn('assets');
		$app['config']->shouldReceive('get')->once()->with('basset::public')->andReturn('public');
		$app['files']->shouldReceive('exists')->once()->with('path/to/public/assets/foo-'.md5($lastModified.PHP_EOL.$lastModified).'.css')->andReturn(false);
		$app['url']->shouldReceive('asset')->once()->with('sample.css')->andReturn('www.example.com/sample.css');
		$app['url']->shouldReceive('asset')->once()->with('nested/sample.css')->andReturn('www.example.com/nested/sample.css');
		$app['config']->shouldReceive('has')->once()->with('basset::assets.sample.css')->andReturn(false);
		$app['config']->shouldReceive('has')->once()->with('basset::assets.nested/sample.css')->andReturn(false);
		$basset = new Basset($app);
		$collection = $basset->collection('foo');
		$collection->add('sample.css');
		$collection->add('nested/sample.css');
		$expected = '<link rel="stylesheet" href="www.example.com/sample.css">'.PHP_EOL.'<link rel="stylesheet" href="www.example.com/nested/sample.css">';
		$this->assertEquals($expected, $basset->show('foo.css'));
	}


	public function testShowCollectionInProduction()
	{
		$app = $this->getApplication();
		$app['config']->shouldReceive('get')->once()->with('basset::collections')->andReturn(array());
		$app['files']->shouldReceive('exists')->once()->with('path/to/public/sample.css')->andReturn(true);
		$app['files']->shouldReceive('getRemote')->once()->andReturn('html { background-color: #fff; }');
		$app['files']->shouldReceive('extension')->once()->andReturn('css');
		$app['files']->shouldReceive('lastModified')->once()->andReturn($lastModified = time());
		$app['files']->shouldReceive('exists')->once()->with('path/to/public/nested/sample.css')->andReturn(true);
		$app['files']->shouldReceive('getRemote')->once()->andReturn('html { background-color: #fff; }');
		$app['files']->shouldReceive('extension')->once()->andReturn('css');
		$app['files']->shouldReceive('lastModified')->once()->andReturn($lastModified);
		$app['env'] = 'production';
		$app['config']->shouldReceive('get')->once()->with('basset::production_environment')->andReturn('production');
		$app['config']->shouldReceive('get')->twice()->with('basset::compiling_path')->andReturn('assets');
		$app['config']->shouldReceive('get')->once()->with('basset::public')->andReturn('public');
		$app['files']->shouldReceive('exists')->once()->with('path/to/public/assets/foo-'.md5($lastModified.PHP_EOL.$lastModified).'.css')->andReturn(true);
		$app['url']->shouldReceive('asset')->once()->with('assets/foo-'.md5($lastModified.PHP_EOL.$lastModified).'.css')->andReturn('www.example.com/assets/foo-'.md5($lastModified.PHP_EOL.$lastModified).'.css');
		$app['config']->shouldReceive('has')->once()->with('basset::assets.sample.css')->andReturn(false);
		$app['config']->shouldReceive('has')->once()->with('basset::assets.nested/sample.css')->andReturn(false);
		$basset = new Basset($app);
		$collection = $basset->collection('foo');
		$collection->add('sample.css');
		$collection->add('nested/sample.css');
		$expected = '<link rel="stylesheet" href="www.example.com/assets/foo-'.md5($lastModified.PHP_EOL.$lastModified).'.css">';
		$this->assertEquals($expected, $basset->show('foo.css'));
	}


	public function testDirectoriesCanBeAddedAtRuntime()
	{
		$app = $this->getApplication();
		$app['config']->shouldReceive('get')->once()->with('basset::collections')->andReturn(array());
		$app['config']->shouldReceive('set')->once()->with('basset::directories.example', 'path/to/example');
		$basset = new Basset($app);
		$basset->addDirectory('example', 'path/to/example');
	}


	public function testAssetAliasesCanBeAddedAtRuntime()
	{
		$app = $this->getApplication();
		$app['config']->shouldReceive('get')->once()->with('basset::collections')->andReturn(array());
		$app['config']->shouldReceive('set')->once()->with('basset::assets.example', 'path/to/example');
		$basset = new Basset($app);
		$basset->aliasAsset('example', 'path/to/example');
	}


	public function testCollectionsAreRegisteredFromConfigurationArray()
	{
		$app = $this->getApplication();
		$app['files']->shouldReceive('exists')->once()->with('path/to/public/sample.css')->andReturn(true);
		$app['files']->shouldReceive('getRemote')->once()->andReturn('html { background-color: #fff; }');
		$app['files']->shouldReceive('extension')->once()->andReturn('css');
		$app['files']->shouldReceive('lastModified')->once()->andReturn($lastModified = time());
		$app['env'] = 'development';
		$app['config']->shouldReceive('get')->once()->with('basset::production_environment')->andReturn('production');
		$app['config']->shouldReceive('get')->once()->with('basset::compiling_path')->andReturn('assets');
		$app['config']->shouldReceive('get')->once()->with('basset::public')->andReturn('public');
		$app['files']->shouldReceive('exists')->once()->with('path/to/public/assets/foo-'.md5($lastModified).'.css')->andReturn(false);
		$app['url']->shouldReceive('asset')->once()->with('sample.css')->andReturn('www.example.com/sample.css');
		$app['config']->shouldReceive('has')->once()->with('basset::assets.sample.css')->andReturn(false);
		$app['config']->shouldReceive('get')->once()->with('basset::collections')->andReturn(array('foo' => function($collection)
		{
			$collection->add('sample.css');
		}));
		$basset = new Basset($app);
		$expected = '<link rel="stylesheet" href="www.example.com/sample.css">';
		$this->assertEquals($expected, $basset->show('foo.css'));
	}


	protected function getApplication()
	{
		$app = array();
		$app['files'] = m::mock('Illuminate\Filesystem');
		$app['config'] = m::mock('stdClass');
		$app['url'] = m::mock('stdClass');
		$app['path.public'] = 'path/to/public';
		$app['path.base'] = 'path/to';
		return $app;
	}


}