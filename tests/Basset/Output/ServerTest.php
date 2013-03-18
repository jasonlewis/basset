<?php

use Mockery as m;
use Basset\Output\Server;
use Illuminate\Config\Repository as Config;
use Basset\BassetServiceProvider as Provider;

class OutputServerTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testServingFingerprintedCollection()
    {
        $server = $this->getServerInstance();

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('getName')->twice()->andReturn('foo');
        $collection->shouldReceive('determineExtension')->once()->with('stylesheets')->andReturn('css');
        $collection->shouldReceive('getExcludedAssets')->once()->with('stylesheets')->andReturn(array());

        $server->setCollections(array('foo' => $collection));

        $server->getResolver()->shouldReceive('setCollection')->once()->with($collection);
        $server->getResolver()->shouldReceive('resolveFingerprintedCollection')->once()->with('stylesheets')->andReturn('bar');
        $server->getConfig()->getLoader()->shouldReceive('load')->once()->with('testing', 'build_path', 'basset')->andReturn('assets');
        $server->getSession()->shouldReceive('get')->once()->with(Provider::SESSION_HASH)->andReturn('baz');
        $server->getUrl()->shouldReceive('asset')->once()->with('assets/foo-bar.css')->andReturn('localhost/assets/foo-bar.css');

        $this->assertEquals('<link rel="stylesheet" type="text/css" href="localhost/assets/foo-bar.css" />', $server->stylesheets('foo'));
    }


    public function testServingDynamicCollection()
    {
        $server = $this->getServerInstance();

        $asset = $this->getAssetMock();
        $asset->shouldReceive('getUsablePath')->once()->andReturn('qux/bar.css');
        $asset->shouldReceive('isRemote')->once()->andReturn(false);
        $asset->shouldReceive('getOrder')->once()->andReturn(null);

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('getName')->once()->andReturn('foo');
        $collection->shouldReceive('getAssets')->once()->with('stylesheets')->andReturn(array($asset));

        $server->setCollections(array('foo' => $collection));

        $server->getResolver()->shouldReceive('setCollection')->once()->with($collection);
        $server->getResolver()->shouldReceive('resolveFingerprintedCollection')->once()->with('stylesheets')->andReturn(null);
        $server->getResolver()->shouldReceive('resolveDevelopmentCollection')->once()->with('stylesheets')->andReturn(null);
        $server->getSession()->shouldReceive('get')->once()->with(Provider::SESSION_HASH)->andReturn('baz');
        $server->getUrl()->shouldReceive('asset')->once()->with('baz/foo/qux/bar.css')->andReturn('localhost/baz/foo/qux/bar.css');

        $this->assertEquals('<link rel="stylesheet" type="text/css" href="localhost/baz/foo/qux/bar.css" />', $server->stylesheets('foo'));
    }


    public function testServingFingerprintedCollectionWithExcludedAssets()
    {
        $server = $this->getServerInstance();

        $asset = $this->getAssetMock();
        $asset->shouldReceive('getUsablePath')->once()->andReturn('qux/bar.css');
        $asset->shouldReceive('isRemote')->once()->andReturn(false);
        $asset->shouldReceive('getOrder')->once()->andReturn(null);

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('getName')->twice()->andReturn('foo');
        $collection->shouldReceive('determineExtension')->once()->with('stylesheets')->andReturn('css');
        $collection->shouldReceive('getExcludedAssets')->once()->with('stylesheets')->andReturn(array($asset));

        $server->setCollections(array('foo' => $collection));

        $server->getResolver()->shouldReceive('setCollection')->once()->with($collection);
        $server->getResolver()->shouldReceive('resolveFingerprintedCollection')->once()->with('stylesheets')->andReturn('bar');
        $server->getConfig()->getLoader()->shouldReceive('load')->once()->with('testing', 'build_path', 'basset')->andReturn('assets');
        $server->getSession()->shouldReceive('get')->once()->with(Provider::SESSION_HASH)->andReturn('baz');

        $server->getUrl()->shouldReceive('asset')->once()->with('assets/foo-bar.css')->andReturn('localhost/assets/foo-bar.css');
        $server->getUrl()->shouldReceive('asset')->once()->with('baz/foo/qux/bar.css')->andReturn('localhost/baz/foo/qux/bar.css');

        $response = array(
            '<link rel="stylesheet" type="text/css" href="localhost/assets/foo-bar.css" />',
            '<link rel="stylesheet" type="text/css" href="localhost/baz/foo/qux/bar.css" />'
        );

        $this->assertEquals(array_to_newlines($response), $server->stylesheets('foo'));
    }


    public function testServingDynamicCollectionWithOrderedAssets()
    {
        $assets = array(
            $this->getAssetMock(),
            $this->getAssetMock()
        );
        $assets[0]->shouldReceive('getUsablePath')->once()->andReturn('foo.css');
        $assets[0]->shouldReceive('isRemote')->once()->andReturn(false);
        $assets[0]->shouldReceive('getOrder')->once()->andReturn(null);
        $assets[1]->shouldReceive('getUsablePath')->once()->andReturn('bar.css');
        $assets[1]->shouldReceive('isRemote')->once()->andReturn(false);
        $assets[1]->shouldReceive('getOrder')->once()->andReturn(1);

        $server = $this->getServerInstance();

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('getName')->once()->andReturn('foo');
        $collection->shouldReceive('getAssets')->once()->with('stylesheets')->andReturn($assets);

        $server->setCollections(array('foo' => $collection));

        $server->getResolver()->shouldReceive('setCollection')->once()->with($collection);
        $server->getResolver()->shouldReceive('resolveFingerprintedCollection')->once()->with('stylesheets')->andReturn(null);
        $server->getResolver()->shouldReceive('resolveDevelopmentCollection')->once()->with('stylesheets')->andReturn(null);
        $server->getSession()->shouldReceive('get')->once()->with(Provider::SESSION_HASH)->andReturn('baz');
        $server->getUrl()->shouldReceive('asset')->once()->with('baz/foo/foo.css')->andReturn('localhost/baz/foo/foo.css');
        $server->getUrl()->shouldReceive('asset')->once()->with('baz/foo/bar.css')->andReturn('localhost/baz/foo/bar.css');

        $response = array(
            '<link rel="stylesheet" type="text/css" href="localhost/baz/foo/bar.css" />',
            '<link rel="stylesheet" type="text/css" href="localhost/baz/foo/foo.css" />'
        );

        $this->assertEquals(array_to_newlines($response), $server->stylesheets('foo'));
    }


    protected function getServerInstance()
    {
        return new Server($this->getResolverMock(), $this->getConfigInstance(), $this->getSessionMock(), $this->getUrlMock());
    }


    protected function getAssetMock()
    {
        return m::mock('Basset\Asset');
    }


    protected function getCollectionMock()
    {
        return m::mock('Basset\Collection');
    }


    protected function getResolverMock()
    {
        return m::mock('Basset\Output\Resolver');
    }


    protected function getConfigInstance()
    {
        return new Config(m::mock('Illuminate\Config\LoaderInterface'), 'testing');
    }


    protected function getSessionMock()
    {
        return m::mock('Illuminate\Session\Store');
    }


    protected function getUrlMock()
    {
        return m::mock('Illuminate\Routing\UrlGenerator');
    }


}