<?php

use Mockery as m;
use Basset\Output\Resolver;
use Basset\Manifest\Repository;

class OutputResolverTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testResolvingOfFingerprtinedCollection()
    {
        $collection = m::mock('Basset\Collection');
        $collection->shouldReceive('getName')->twice()->andReturn('foo');

        $files = m::mock('Illuminate\Filesystem\Filesystem');
        $files->shouldReceive('exists')->once()->with('path/to/meta/collections.json')->andReturn(true);
        $files->shouldReceive('get')->once()->with('path/to/meta/collections.json')->andReturn('{"foo":{"fingerprints":{"stylesheets":"bar"}}}');

        $config = m::mock('Illuminate\Config\Repository');
        $config->shouldReceive('get')->twice()->with('basset::production', array())->andReturn('local', 'production');

        $repository = new Repository($files, 'path/to/meta');

        $repository->load();

        $resolver = new Resolver($repository, $config, 'production');

        $this->assertNull($resolver->resolveFingerprintedCollection($collection, 'stylesheets'));
        $this->assertEquals('bar', $resolver->resolveFingerprintedCollection($collection, 'stylesheets'));
    }


}