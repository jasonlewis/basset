<?php

use Mockery as m;
use Basset\Output\Builder;

class OutputBuilderTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testOutputtingFingerprintedCollection()
    {
        $collection = $this->getCollectionMock();
        $collection->shouldReceive('getName')->twice()->andReturn('foo');
        $collection->shouldReceive('determineExtension')->once()->with('styles')->andReturn('css');
        $collection->shouldReceive('getIgnoredAssets')->once()->with('styles')->andReturn(array());

        $resolver = $this->getResolverMock();
        $resolver->shouldReceive('resolveFingerprintedCollection')->once()->with($collection, 'styles')->andReturn('bar');

        $config = $this->getConfigMock();
        $config->shouldReceive('get')->once()->with('basset::build_path')->andReturn('assets');

        $session = $this->getSessionMock();
        $session->shouldReceive('get')->once()->with('basset_hash')->andReturn('baz');

        $url = $this->getUrlMock();
        $url->shouldReceive('asset')->once()->with('assets/foo-bar.css')->andReturn('localhost/assets/foo-bar.css');

        $builder = new Builder($resolver, $config, $session, $url, array('foo' => $collection));

        $this->assertEquals('<link rel="stylesheet" type="text/css" href="localhost/assets/foo-bar.css" />', $builder->styles('foo'));
    }


    public function testOutputtingDevelopmentCollection()
    {
        $asset = $this->getAssetMock();
        $asset->shouldReceive('getRelativePath')->once()->andReturn('baz/bar.scss');
        $asset->shouldReceive('isIgnored')->once()->andReturn(false);
        $asset->shouldReceive('isRemote')->once()->andReturn(false);

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('getName')->once()->andReturn('foo');
        $collection->shouldReceive('getAssets')->once()->with('styles')->andReturn(array($asset));

        $resolver = $this->getResolverMock();
        $resolver->shouldReceive('resolveFingerprintedCollection')->once()->with($collection, 'styles')->andReturn(null);
        $resolver->shouldReceive('resolveDevelopmentCollection')->once()->with($collection, 'styles')->andReturn(array('baz/bar.scss' => 'baz/bar.css'));

        $config = $this->getConfigMock();
        $config->shouldReceive('get')->once()->with('basset::build_path')->andReturn('assets');

        $session = $this->getSessionMock();

        $url = $this->getUrlMock();
        $url->shouldReceive('asset')->once()->with('assets/foo/baz/bar.css')->andReturn('localhost/assets/foo/baz/bar.css');

        $builder = new Builder($resolver, $config, $session, $url, array('foo' => $collection));

        $this->assertEquals('<link rel="stylesheet" type="text/css" href="localhost/assets/foo/baz/bar.css" />', $builder->styles('foo'));
    }


    public function testOutputtingDynamicCollection()
    {
        $asset = $this->getAssetMock();
        $asset->shouldReceive('getRelativePath')->once()->andReturn('qux/bar.scss');
        $asset->shouldReceive('isRemote')->once()->andReturn(false);
        $asset->shouldReceive('getPosition')->once()->andReturn(null);

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('getName')->once()->andReturn('foo');
        $collection->shouldReceive('getAssets')->once()->with('styles')->andReturn(array($asset));

        $resolver = $this->getResolverMock();
        $resolver->shouldReceive('resolveFingerprintedCollection')->once()->with($collection, 'styles')->andReturn(null);
        $resolver->shouldReceive('resolveDevelopmentCollection')->once()->with($collection, 'styles')->andReturn(null);

        $config = $this->getConfigMock();

        $session = $this->getSessionMock();
        $session->shouldReceive('get')->once()->with('basset_hash')->andReturn('baz');

        $url = $this->getUrlMock();
        $url->shouldReceive('asset')->once()->with('baz/foo/qux/bar.scss')->andReturn('localhost/baz/foo/qux/bar.scss');

        $builder = new Builder($resolver, $config, $session, $url, array('foo' => $collection));

        $this->assertEquals('<link rel="stylesheet" type="text/css" href="localhost/baz/foo/qux/bar.scss" />', $builder->styles('foo'));
    }


    public function testOuputtingCollectionWithIgnoredAssets()
    {
        $asset = $this->getAssetMock();
        $asset->shouldReceive('getRelativePath')->once()->andReturn('foo.scss');
        $asset->shouldReceive('isRemote')->once()->andReturn(false);
        $asset->shouldReceive('getPosition')->once()->andReturn(null);

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('getName')->twice()->andReturn('foo');
        $collection->shouldReceive('determineExtension')->once()->with('styles')->andReturn('css');
        $collection->shouldReceive('getIgnoredAssets')->once()->with('styles')->andReturn(array($asset));

        $resolver = $this->getResolverMock();
        $resolver->shouldReceive('resolveFingerprintedCollection')->once()->with($collection, 'styles')->andReturn('baz');

        $config = $this->getConfigMock();
        $config->shouldReceive('get')->once()->with('basset::build_path')->andReturn('assets');

        $session = $this->getSessionMock();
        $session->shouldReceive('get')->once()->with('basset_hash')->andReturn('baz');

        $url = $this->getUrlMock();
        $url->shouldReceive('asset')->once()->with('baz/foo/foo.scss')->andReturn('localhost/baz/foo/foo.scss');
        $url->shouldReceive('asset')->once()->with('assets/foo-baz.css')->andReturn('localhost/assets/foo-baz.css');

        $builder = new Builder($resolver, $config, $session, $url, array('foo' => $collection));

        $responses = array(
            '<link rel="stylesheet" type="text/css" href="localhost/assets/foo-baz.css" />',
            '<link rel="stylesheet" type="text/css" href="localhost/baz/foo/foo.scss" />'
        );

        $this->assertEquals(array_to_newlines($responses), $builder->styles('foo'));
    }


    public function testAssetsAreOrderedCorrectlyWhenOutputted()
    {
        $assets = array(
            $this->getAssetMock(),
            $this->getAssetMock()
        );
        $assets[0]->shouldReceive('getRelativePath')->once()->andReturn('foo.css');
        $assets[0]->shouldReceive('isRemote')->once()->andReturn(false);
        $assets[0]->shouldReceive('getPosition')->once()->andReturn(2);
        $assets[1]->shouldReceive('getRelativePath')->once()->andReturn('bar.css');
        $assets[1]->shouldReceive('isRemote')->once()->andReturn(false);
        $assets[1]->shouldReceive('getPosition')->once()->andReturn(1);

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('getName')->once()->andReturn('qux');
        $collection->shouldReceive('getAssets')->once()->with('styles')->andReturn($assets);

        $resolver = $this->getResolverMock();
        $resolver->shouldReceive('resolveFingerprintedCollection')->once()->with($collection, 'styles')->andReturn(null);
        $resolver->shouldReceive('resolveDevelopmentCollection')->once()->with($collection, 'styles')->andReturn(null);

        $config = $this->getConfigMock();

        $session = $this->getSessionMock();
        $session->shouldReceive('get')->once()->with('basset_hash')->andReturn('baz');

        $url = $this->getUrlMock();
        $url->shouldReceive('asset')->once()->with('baz/qux/foo.css')->andReturn('localhost/baz/qux/foo.css');
        $url->shouldReceive('asset')->once()->with('baz/qux/bar.css')->andReturn('localhost/baz/qux/bar.css');

        $builder = new Builder($resolver, $config, $session, $url, array('foo' => $collection));

        $response = array(
            '<link rel="stylesheet" type="text/css" href="localhost/baz/qux/bar.css" />',
            '<link rel="stylesheet" type="text/css" href="localhost/baz/qux/foo.css" />'
        );

        $this->assertEquals(array_to_newlines($response), $builder->styles('foo'));
    }


    public function testAssetsAreOrderedCorrectlyWhenOutputtedWithFingerprintedCollection()
    {
        $asset = $this->getAssetMock();
        $asset->shouldReceive('getRelativePath')->once()->andReturn('qux.css');
        $asset->shouldReceive('isRemote')->once()->andReturn(false);
        $asset->shouldReceive('getPosition')->once()->andReturn(1);

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('getName')->twice()->andReturn('foo');
        $collection->shouldReceive('determineExtension')->once()->with('styles')->andReturn('css');
        $collection->shouldReceive('getIgnoredAssets')->once()->with('styles')->andReturn(array($asset));

        $resolver = $this->getResolverMock();
        $resolver->shouldReceive('resolveFingerprintedCollection')->once()->with($collection, 'styles')->andReturn('bar');

        $config = $this->getConfigMock();
        $config->shouldReceive('get')->once()->with('basset::build_path')->andReturn('assets');

        $session = $this->getSessionMock();
        $session->shouldReceive('get')->once()->with('basset_hash')->andReturn('baz');

        $url = $this->getUrlMock();
        $url->shouldReceive('asset')->once()->with('assets/foo-bar.css')->andReturn('localhost/assets/foo-bar.css');
        $url->shouldReceive('asset')->once()->with('baz/foo/qux.css')->andReturn('localhost/baz/foo/qux.css');

        $builder = new Builder($resolver, $config, $session, $url, array('foo' => $collection));

        $response = array(
            '<link rel="stylesheet" type="text/css" href="localhost/baz/foo/qux.css" />',
            '<link rel="stylesheet" type="text/css" href="localhost/assets/foo-bar.css" />'
        );

        $this->assertEquals(array_to_newlines($response), $builder->styles('foo'));
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


    protected function getConfigMock()
    {
        return m::mock('Illuminate\Config\Repository');
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