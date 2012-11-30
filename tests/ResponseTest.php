<?php

use Mockery as m;

class ResponseTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testCanCreateResponse()
	{
		$app = new Illuminate\Container;
		$app['files'] = m::mock('Illuminate\Filesystem');
		$app['config'] = m::mock('Illuminate\Config\Repository');
		$app['request'] = m::mock('Illuminate\Http\Request');
		$response = new Basset\Response($app);
		$this->assertInstanceOf('Basset\Response', $response);
	}


	public function testCanVerifyRequest()
	{
		$app = new Illuminate\Container;
		$app['files'] = m::mock('Illuminate\Filesystem');
		$app['request'] = m::mock('Illuminate\Http\Request');
		$app['request']->shouldReceive('path')->once()->andReturn('assets/example.css');
		$app['config'] = m::mock('StdClass');
		$app['config']->shouldReceive('get')->with('basset::handles')->andReturn('assets');
		$response = new Basset\Response($app);
		$this->assertTrue($response->verifyRequest());
		$app['request']->shouldReceive('path')->once()->andReturn('testing/example.css');
		$this->assertFalse($response->verifyRequest());
	}


	public function testCanGetAssetResponse()
	{
		$app = new Illuminate\Container;
		$app['path.public'] = 'path/to/public';
		$app['files'] = m::mock('Illuminate\Filesystem');
		$app['files']->shouldReceive('exists')->once()->andReturn(true);
		$app['files']->shouldReceive('get')->once()->andReturn('html { background-color: #fff; }');
		$app['files']->shouldReceive('extension')->once()->andReturn('css');
		$app['files']->shouldReceive('lastModified')->once()->andReturn(time());
		$app['request'] = m::mock('Illuminate\Http\Request');
		$app['request']->shouldReceive('path')->once()->andReturn('assets/sample.css');
		$app['request']->shouldReceive('getBaseUrl')->once()->andReturn('');
		$app['config'] = m::mock('StdClass');
		$app['config']->shouldReceive('get')->with('basset::directories')->andReturn(array('foo' => 'path: '.__DIR__));
		$app['config']->shouldReceive('get')->with('basset::handles')->andReturn('assets');
		$response = new Basset\Response($app);
		$response->prepare();
		ob_start();
		$response->getResponse()->sendContent();
		$this->assertEquals('html { background-color: #fff; }', ob_get_clean());
	}


}