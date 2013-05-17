<?php

use Mockery as m;
use Basset\Server;
use Illuminate\Http\Request;
use Basset\Manifest\Manifest;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Routing\RouteCollection;

class ServerTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function setUp()
    {
        $this->environment = m::mock('Basset\Environment');
        $this->manifest = new Manifest(new Filesystem, 'meta');
        $this->config = m::mock('Illuminate\Config\Repository');
        $this->url = new UrlGenerator(new RouteCollection, Request::create('http://localhost', 'GET'));

        $this->server = new Server($this->environment, $this->manifest, $this->config, $this->url, 'testing');
    }


    public function testServingInvalidCollectionReturnsNothing()
    {
        $this->environment->shouldReceive('offsetExists')->once()->with('foo')->andReturn(false);
        $this->assertNull($this->server->serve('foo', 'stylesheets'));
    }


    /**
     * @dataProvider providerServingProductionCollectionReturnsExpectedHtml
     */
    public function testServingProductionCollectionReturnsExpectedHtml($name, $group, $fingerprint, $expected)
    {
        $this->environment->shouldReceive(array('offsetExists' => true, 'offsetGet' => $collection = m::mock('Basset\Collection')))->with($name);

        $this->config->shouldReceive('get')->once()->with('basset::production')->andReturn('testing');

        $collection->shouldReceive('getAssetsOnlyExcluded')->with($group)->andReturn(array());
        $collection->shouldReceive('getIdentifier')->andReturn($name);

        $entry = $this->manifest->make($collection);
        $entry->setProductionFingerprint($group, $fingerprint);

        $this->config->shouldReceive('get')->with('basset::build_path')->andReturn('assets');

        $this->assertEquals($expected, $this->server->{$group}($name));
    }


    public function providerServingProductionCollectionReturnsExpectedHtml()
    {
        return array(
            array('foo', 'stylesheets', 'bar-123.css', '<link rel="stylesheet" type="text/css" href="http://localhost/assets/bar-123.css" />'),
            array('bar', 'javascripts', 'baz-321.js', '<script src="http://localhost/assets/baz-321.js"></script>'),
        );
    }


    public function testServingDevelopmentCollectionReturnsExpectedHtml()
    {
        $this->environment->shouldReceive(array('offsetExists' => true, 'offsetGet' => $collection = m::mock('Basset\Collection')))->with('foo');
        
        $this->config->shouldReceive('get')->once()->with('basset::production')->andReturn('prod');

        $collection->shouldReceive('getAssetsOnlyExcluded')->with('stylesheets')->andReturn(array());
        $collection->shouldReceive('getIdentifier')->andReturn('foo');

        $entry = $this->manifest->make($collection);
        $entry->addDevelopmentAsset('bar.less', 'bar.css', 'stylesheets');
        $entry->addDevelopmentAsset('baz.sass', 'baz.css', 'stylesheets');

        $this->config->shouldReceive('get')->with('basset::build_path')->andReturn('assets');

        $expected = '<link rel="stylesheet" type="text/css" href="http://localhost/assets/foo/bar.css" />'.PHP_EOL.
                    '<link rel="stylesheet" type="text/css" href="http://localhost/assets/foo/baz.css" />';
        $this->assertEquals($expected, $this->server->serve('foo', 'stylesheets'));
    }


    public function testExcludedAssetsAreServedBeforeBuiltCollectionHtml()
    {
        $this->environment->shouldReceive(array('offsetExists' => true, 'offsetGet' => $collection = m::mock('Basset\Collection')))->with('foo');
        
        $this->config->shouldReceive('get')->once()->with('basset::production')->andReturn('testing');

        $collection->shouldReceive('getAssetsOnlyExcluded')->with('stylesheets')->andReturn(array($asset = m::mock('Basset\Asset')));
        $collection->shouldReceive('getIdentifier')->andReturn('foo');

        $asset->shouldReceive('getRelativePath')->andReturn('css/baz.css');

        $entry = $this->manifest->make($collection);
        $entry->setProductionFingerprint('stylesheets', 'bar-123.css');

        $this->config->shouldReceive('get')->with('basset::build_path')->andReturn('assets');

        $expected = '<link rel="stylesheet" type="text/css" href="http://localhost/css/baz.css" />'.PHP_EOL.
                    '<link rel="stylesheet" type="text/css" href="http://localhost/assets/bar-123.css" />';
        $this->assertEquals($expected, $this->server->collection('foo.css'));
    }


    public function testServingCollectionsWithCustomFormat()
    {
        $this->environment->shouldReceive(array('offsetExists' => true, 'offsetGet' => $collection = m::mock('Basset\Collection')))->with('foo');
        
        $this->config->shouldReceive('get')->once()->with('basset::production')->andReturn('testing');

        $collection->shouldReceive('getAssetsOnlyExcluded')->with('stylesheets')->andReturn(array());
        $collection->shouldReceive('getIdentifier')->andReturn('foo');

        $entry = $this->manifest->make($collection);
        $entry->setProductionFingerprint('stylesheets', 'foo-123.css');

        $this->config->shouldReceive('get')->with('basset::build_path')->andReturn('assets');

        $expected = '<link rel="stylesheet" type="text/css" href="http://localhost/assets/foo-123.css" media="print" />';
        $this->assertEquals($expected, $this->server->stylesheets('foo', '<link rel="stylesheet" type="text/css" href="%s" media="print" />'));
    }


}