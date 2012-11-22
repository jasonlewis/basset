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
		$app['config'] = new Illuminate\Config\Repository(m::mock('Illuminate\Config\LoaderInterface'), 'production');
		$app['config']->getLoader()->shouldReceive('load')->once()->with('production', 'handles', 'basset')->andReturn('assets');
		$app['config']->getLoader()->shouldReceive('exists')->once()->andReturn(true);
		$response = new Basset\Response($app);
		$this->assertTrue($response->verifyRequest());
		$app['request']->shouldReceive('path')->once()->andReturn('testing/example.css');
		$this->assertFalse($response->verifyRequest());
	}


	public function testCanGetAssetResponse()
	{
		$app = new Illuminate\Container;
		$app['files'] = m::mock('Illuminate\Filesystem');
		$app['request'] = m::mock('Illuminate\Http\Request');
		$app['request']->shouldReceive('path')->once()->andReturn('assets/sample.css');
		$app['request']->shouldReceive('getBaseUrl')->once()->andReturn('');
		$app['config'] = array(
			'basset::handles' => 'assets',
			'basset::directories' => array('foo' => 'path: '.__DIR__.'/fixtures')
		);
		$response = new Basset\Response($app);
		$response->prepare();
		ob_start();
		$response->getResponse()->sendContent();
		$this->assertEquals('html { background-color: #fff; }', ob_get_clean());
	}


}