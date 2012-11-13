<?php

use Mockery as m;
use Basset\Response;

class ResponseTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testCanCreateResponse()
	{
		$config = m::mock('Illuminate\Config\Repository');
		$files = m::mock('Illuminate\Filesystem');
		$request = m::mock('Illuminate\Http\Request');

		$response = new Response($request, $files, $config);

		$this->assertInstanceOf('Basset\Response', $response);
	}


	public function testCanVerifyRequest()
	{
		$config = new Illuminate\Config\Repository(m::mock('Illuminate\Config\FileLoader'), 'production');
		$files = m::mock('Illuminate\Filesystem');
		$request = m::mock('Illuminate\Http\Request');

		$config->getLoader()->shouldReceive('load')->once()->with('production', 'basset', null)->andReturn(array(
			'handles' => 'assets'
		));

		$request->shouldReceive('getRequestUri')->once()->andReturn('/assets/example.css');

		$response = new Response($request, $files, $config);

		$this->assertTrue($response->verifyRequest());
		$request->shouldReceive('getRequestUri')->once()->andReturn('/something/example.css');
		$this->assertFalse($response->verifyRequest());
	}


	public function testCanGetAssetResponse()
	{
		$config = new Illuminate\Config\Repository(m::mock('Illuminate\Config\FileLoader'), 'production');
		$files = m::mock('Illuminate\Filesystem');
		$request = m::mock('Illuminate\Http\Request');

		$config->getLoader()->shouldReceive('load')->once()->with('production', 'basset', null)->andReturn(array(
			'directories' => array('foo' => 'path: '.__DIR__.'/fixtures'),
			'handles' => 'assets'
		));

		$request->shouldReceive('getRequestUri')->once()->andReturn('/assets/sample.css');

		$response = new Response($request, $files, $config);

		$response->prepare();

		ob_start();

		$response->getResponse()->sendContent();

		$this->assertEquals('html { background-color: #fff; }', ob_get_clean());
	}


}