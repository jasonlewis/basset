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
        $files->shouldReceive('get')->with('path/to/meta/collections.json')->andReturn('{"foo":{"development":{"bar":"baz"}}}');
        $repository = new Repository($files, 'path/to/meta');

        $this->assertEquals(array('development' => array('bar' => 'baz'), 'fingerprints' => array()), $repository->load()->getEntry('foo')->toArray());
    }


    public function testMethodsPassThruToManifestInstance()
    {
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->with('path/to/meta/collections.json')->andReturn(true);
        $files->shouldReceive('get')->with('path/to/meta/collections.json')->andReturn('{"foo":{"development":{"bar":"baz"}}}');
        $repository = new Repository($files, 'path/to/meta');

        $repository->load();

        $this->assertTrue($repository->hasEntry('foo'));
    }


    public function testRegisteringOfCollectionWithRepository()
    {
        $fingerprint = array('styles' => md5('baz'));

        $manifestArray = array('qux' => array('development' => array(), 'fingerprints' => $fingerprint));

        $files = $this->getFilesMock();
        $files->shouldReceive('put')->once()->with('path/to/meta/collections.json', json_encode($manifestArray));
        $collection = $this->getCollectionMock();
        $collection->shouldReceive('getName')->once()->andReturn('qux');
        $repository = new Repository($files, 'path/to/meta');

        $repository->register($collection, $fingerprint);

        $this->assertEquals($manifestArray['qux'], $repository->getEntry('qux')->toArray());
    }


    public function testRegisterCollectionWithRepositoryAsDevelopment()
    {
        $fingerprint = array('styles' => md5('baz'));

        $manifestArray = array('qux' => array('development' => array('styles' => array('foo/bar/baz.scss' => 'foo/bar/baz.css')), 'fingerprints' => $fingerprint));

        $files = $this->getFilesMock();
        $files->shouldReceive('put')->once()->with('path/to/meta/collections.json', json_encode($manifestArray));
        $asset = $this->getAssetMock();
        $asset->shouldReceive('getRelativePath')->once()->andReturn('foo/bar/baz.scss');
        $asset->shouldReceive('getUsableExtension')->once()->andReturn('css');
        $asset->shouldReceive('getGroup')->once()->andReturn('styles');
        $asset->shouldReceive('isRemote')->once()->andReturn(false);
        $collection = $this->getCollectionMock();
        $collection->shouldReceive('getName')->once()->andReturn('qux');
        $collection->shouldReceive('getAssets')->once()->andReturn(array($asset));
        $repository = new Repository($files, 'path/to/meta');

        $repository->register($collection, $fingerprint, true);

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