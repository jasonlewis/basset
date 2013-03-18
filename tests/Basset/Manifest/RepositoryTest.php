<?php

use Mockery as m;
use Basset\Manifest\Repository;

class RepositoryTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testLoadingOfManifest()
    {
        $repository = new Repository($files = $this->getFilesMock(), 'path/to/meta');

        $files->shouldReceive('exists')->with('path/to/meta/collections.json')->andReturn(true);
        $files->shouldReceive('get')->with('path/to/meta/collections.json')->andReturn('{"foo":{"fingerprints":{"stylesheets":"bar"},"development":{"stylesheets":{"baz/qux.scss":"baz/qux.css"}}}}');

        $this->assertEquals(array(
            'fingerprints' => array('stylesheets' => 'bar'),
            'development' => array('stylesheets' => array('baz/qux.scss' => 'baz/qux.css'))
        ), $repository->load()->getEntry('foo')->toArray());
    }


    public function testMethodsArePassedThroughToManifestInstance()
    {
        $repository = new Repository($files = $this->getFilesMock(), 'path/to/meta');
        $files->shouldReceive('exists')->with('path/to/meta/collections.json')->andReturn(true);
        $files->shouldReceive('get')->with('path/to/meta/collections.json')->andReturn('{"foo":{"fingerprints":{"stylesheets":"bar"}}}');

        $repository->load();

        $this->assertTrue($repository->hasEntry('foo'));
    }


    public function testRegisterCollectionWithRepository()
    {
        $fingerprint = array('stylesheets' => md5('baz'));

        $manifest = array('qux' => array('fingerprints' => $fingerprint, 'development' => array()));

        $repository = new Repository($files = $this->getFilesMock(), 'path/to/meta');
        $files->shouldReceive('put')->once()->with('path/to/meta/collections.json', json_encode($manifest));

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('getName')->once()->andReturn('qux');

        $repository->register($collection, $fingerprint);

        $this->assertEquals($manifest['qux'], $repository->getEntry('qux')->toArray());
    }


    protected function getFilesMock()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
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