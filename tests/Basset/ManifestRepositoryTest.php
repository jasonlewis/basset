<?php

use Mockery as m;
use Basset\Manifest\Repository;

class ManifestRepositoryTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testLoadingOfManifest()
    {
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->with('path/to/meta/collections.json')->andReturn(true);
        $files->shouldReceive('get')->with('path/to/meta/collections.json')->andReturn('{"foo":{"fingerprints":{"styles":"bar"}}}');
        $repository = new Repository($files, 'path/to/meta');

        $this->assertEquals(array('fingerprints' => array('styles' => 'bar')), $repository->load()->getEntry('foo')->toArray());
    }


    public function testMethodsPassThruToManifestInstance()
    {
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->with('path/to/meta/collections.json')->andReturn(true);
        $files->shouldReceive('get')->with('path/to/meta/collections.json')->andReturn('{"foo":{"fingerprints":{"styles":"bar"}}}');
        $repository = new Repository($files, 'path/to/meta');

        $repository->load();

        $this->assertTrue($repository->hasEntry('foo'));
    }


    public function testRegisteringOfCollectionWithRepository()
    {
        $fingerprint = array('styles' => md5('baz'));

        $manifestArray = array('qux' => array('fingerprints' => $fingerprint));

        $files = $this->getFilesMock();
        $files->shouldReceive('put')->once()->with('path/to/meta/collections.json', json_encode($manifestArray));
        $collection = $this->getCollectionMock();
        $collection->shouldReceive('getName')->once()->andReturn('qux');
        $repository = new Repository($files, 'path/to/meta');

        $repository->register($collection, $fingerprint);

        $this->assertEquals($manifestArray['qux'], $repository->getEntry('qux')->toArray());
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