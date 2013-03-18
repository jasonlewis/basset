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
        $development = array();

        $manifest = array('qux' => array('fingerprints' => $fingerprint, 'development' => $development));

        $repository = new Repository($files = $this->getFilesMock(), 'path/to/meta');
        $files->shouldReceive('put')->once()->with('path/to/meta/collections.json', json_encode($manifest));

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('getName')->once()->andReturn('qux');

        $repository->register($collection, $fingerprint);

        $this->assertEquals($manifest['qux'], $repository->getEntry('qux')->toArray());
    }


    public function testRegisterDevelopmentCollectionWithRepository()
    {
        $fingerprint = array('stylesheets' => md5('baz'));
        $development = array(
            'stylesheets' => array('http://bar.qux/yin.css' => 'http://bar.qux/yin.css'),
            'javascripts' => array('foo/bar.coffee' => 'foo/bar.js')
        );

        $manifest = array('qux' => array('fingerprints' => $fingerprint, 'development' => $development));

        $repository = new Repository($files = $this->getFilesMock(), 'path/to/meta');

        $files->shouldReceive('put')->once()->with('path/to/meta/collections.json', json_encode($manifest));

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('getName')->once()->andReturn('qux');
        $collection->shouldReceive('getAssets')->once()->andReturn(array(
            $assets[] = $this->getAssetMock(),
            $assets[] = $this->getAssetMock()
        ));

        $assets[0]->shouldReceive('getRelativePath')->once()->andReturn('http://bar.qux/yin.css');
        $assets[0]->shouldReceive('getGroup')->once()->andReturn('stylesheets');
        $assets[0]->shouldReceive('isRemote')->once()->andReturn(true);
        $assets[1]->shouldReceive('getRelativePath')->once()->andReturn('foo/bar.coffee');
        $assets[1]->shouldReceive('getUsablePath')->once()->andReturn('foo/bar.js');
        $assets[1]->shouldReceive('getGroup')->once()->andReturn('javascripts');
        $assets[1]->shouldReceive('isRemote')->once()->andReturn(false);

        $repository->register($collection, $fingerprint, true);

        $this->assertEquals($manifest['qux'], $entry = $repository->getEntry('qux')->toArray());
        $this->assertEquals($development, $entry['development']);
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