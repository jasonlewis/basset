<?php

use Mockery as m;
use Basset\Output\Resolver;
use Basset\Manifest\Repository as ManifestRepository;
use Illuminate\Config\Repository as ConfigRepository;

class OutputResolverTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testResolvingOfFingerprtinedCollectionInProductionEnvironment()
    {
        $collection = m::mock('Basset\Collection');
        $collection->shouldReceive('getName')->once()->andReturn('foo');

        $files = m::mock('Illuminate\Filesystem\Filesystem');
        $files->shouldReceive('exists')->once()->with('path/to/meta/collections.json')->andReturn(true);
        $files->shouldReceive('get')->once()->with('path/to/meta/collections.json')->andReturn('{"foo":{"fingerprints":{"stylesheets":"bar"}}}');

        $config = new ConfigRepository(m::mock('Illuminate\Config\LoaderInterface'), 'testing');
        $config->getLoader()->shouldReceive('load')->once()->with('testing', 'production', 'basset')->andReturn('production');

        $repository = new ManifestRepository($files, 'path/to/meta');

        $repository->load();

        $resolver = new Resolver($repository, $config, 'production');

        $this->assertEquals('bar', $resolver->resolveFingerprintedCollection($collection, 'stylesheets'));
    }


    public function testResolvingOfFingerprtinedCollectionInDevelopmentEnvironment()
    {
        $collection = m::mock('Basset\Collection');
        $collection->shouldReceive('getName')->once()->andReturn('foo');

        $files = m::mock('Illuminate\Filesystem\Filesystem');
        $files->shouldReceive('exists')->once()->with('path/to/meta/collections.json')->andReturn(true);
        $files->shouldReceive('get')->once()->with('path/to/meta/collections.json')->andReturn('{"foo":{"fingerprints":{"stylesheets":"bar"}}}');

        $config = new ConfigRepository(m::mock('Illuminate\Config\LoaderInterface'), 'testing');
        $config->getLoader()->shouldReceive('load')->once()->with('testing', 'production', 'basset')->andReturn(array('local'));

        $repository = new ManifestRepository($files, 'path/to/meta');

        $repository->load();

        $resolver = new Resolver($repository, $config, 'production');

        $this->assertNull($resolver->resolveFingerprintedCollection($collection, 'stylesheets'));
    }


}