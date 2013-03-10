<?php

use Mockery as m;
use Basset\Output\Controller;

class OutputControllerTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testAssetIsProcessedAndCorrectResponseAndHeadersAreReturned()
    {
        $controller = new Controller($env = $this->getEnvMock());

        $env->shouldReceive('hasCollection')->once()->with('foo')->andReturn(true);
        $env->shouldReceive('collection')->once()->with('foo')->andReturn($collection = $this->getCollectionMock());

        $collection->shouldReceive('getAssets')->once()->andReturn(array($asset = $this->getAssetMock()));

        $asset->shouldReceive('getUsablePath')->once()->andReturn('bar/baz.css');
        $asset->shouldReceive('build')->once()->andReturn('body { background-color: #fff; }');
        $asset->shouldReceive('getUsableExtension')->once()->andReturn('css');

        $response = $controller->processAsset('foo', 'bar/baz.css');

        $this->assertTrue($response->isOk());
        $this->assertEquals($response->headers->get('content-type'), 'text/css');
        $this->assertEquals('body { background-color: #fff; }', $response->getContent());
    }


    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testInvalidCollectionThrowsNotFoundException()
    {
        $controller = new Controller($env = $this->getEnvMock());

        $env->shouldReceive('hasCollection')->once()->with('foo')->andReturn(true);
        $env->shouldReceive('collection')->once()->with('foo')->andReturn($collection = $this->getCollectionMock());

        $collection->shouldReceive('getAssets')->once()->andReturn(array());

        $controller->processAsset('foo', 'bar/baz.css');
    }


    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testInvalidAssetThrowsNotFoundException()
    {
        $controller = new Controller($env = $this->getEnvMock());

        $env->shouldReceive('hasCollection')->once()->with('foo')->andReturn(false);

        $controller->processAsset('foo', 'bar/baz.css');
    }


    protected function getEnvMock()
    {
        return m::mock('Basset\Environment');
    }


    protected function getCollectionMock()
    {
        return m::mock('Basset\Collection');
    }


    protected function getAssetMock()
    {
        return m::mock('Basset\Asset');
    }


}