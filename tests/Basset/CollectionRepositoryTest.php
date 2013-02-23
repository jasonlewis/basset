<?php

use Mockery as m;
use JasonLewis\Basset\CollectionRepository;

class CollectionRepositoryTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testLoadingOfManifest()
    {
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->with('path/to/meta/collections.json')->andReturn(true);
        $files->shouldReceive('get')->with('path/to/meta/collections.json')->andReturn('{"foo":"bar"}');
        $repository = new CollectionRepository($files, 'path/to/meta');

        $this->assertEquals(array('foo' => 'bar'), $repository->load());
    }


    public function testFindReturnsExistingEntry()
    {
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->with('path/to/meta/collections.json')->andReturn(true);
        $files->shouldReceive('get')->with('path/to/meta/collections.json')->andReturn('{"foo":"bar"}');
        $repository = new CollectionRepository($files, 'path/to/meta');

        $repository->load();

        $this->assertEquals('bar', $repository->find('foo'));
    }


    public function testFindReturnsFreshEntry()
    {
        $files = $this->getFilesMock();
        $files->shouldReceive('exists')->with('path/to/meta/collections.json')->andReturn(true);
        $files->shouldReceive('get')->with('path/to/meta/collections.json')->andReturn('{"foo":"bar"}');
        $repository = new CollectionRepository($files, 'path/to/meta');

        $repository->load();

        $this->assertEquals(array('development' => array(), 'fingerprint' => null), $repository->find('baz'));
    }


    public function testRegisterCollectionWithRepository()
    {
        $fingerprint = md5('baz');

        $manifestArray = array('qux' => array('development' => array(), 'fingerprint' => $fingerprint));

        $files = $this->getFilesMock();
        $files->shouldReceive('put')->once()->with('path/to/meta/collections.json', json_encode($manifestArray));
        $collection = $this->getCollectionMock();
        $collection->shouldReceive('getName')->twice()->andReturn('qux');
        $repository = new CollectionRepository($files, 'path/to/meta');

        $repository->register($collection, $fingerprint);

        $this->assertEquals($manifestArray['qux'], $repository->find('qux'));
    }


    public function testRegisterCollectionWithRepositoryAsDevelopment()
    {
        $fingerprint = md5('baz');

        $manifestArray = array('qux' => array('development' => array('foo/bar/baz.scss' => 'qux/foo/bar/baz.css'), 'fingerprint' => $fingerprint));

        $files = $this->getFilesMock();
        $files->shouldReceive('put')->once()->with('path/to/meta/collections.json', json_encode($manifestArray));
        $asset = $this->getAssetMock();
        $asset->shouldReceive('getRelativePath')->once()->andReturn('foo/bar/baz.scss');
        $asset->shouldReceive('getValidExtension')->once()->andReturn('css');
        $collection = $this->getCollectionMock();
        $collection->shouldReceive('getName')->times(3)->andReturn('qux');
        $collection->shouldReceive('getAssets')->once()->andReturn(array($asset));
        $repository = new CollectionRepository($files, 'path/to/meta');

        $repository->register($collection, $fingerprint, true);

        $this->assertEquals($manifestArray['qux'], $repository->find('qux'));
    }


    protected function getFilesMock()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }


    protected function getCollectionMock()
    {
        return m::mock('JasonLewis\Basset\Collection');
    }


    protected function getAssetMock()
    {
        return m::mock('JasonLewis\Basset\Asset');
    }


}