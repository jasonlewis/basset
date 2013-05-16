<?php

use Mockery as m;

class FilesystemCleanerTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function setUp()
    {
        $this->environment = m::mock('Basset\Environment');
        $this->manifest = m::mock('Basset\Manifest\Repository');
        $this->files = m::mock('Illuminate\Filesystem\Filesystem');

        $this->cleaner = new Basset\Builder\FilesystemCleaner($this->environment, $this->manifest, $this->files, 'path/to/builds');

        $this->manifest->shouldReceive('save')->atLeast()->once();
    }


    public function testForgettingCollectionFromManifestThatNoLongerExistsOnEnvironment()
    {
        $this->environment->shouldReceive('offsetExists')->once()->with('foo')->andReturn(false);
        $this->manifest->shouldReceive('get')->once()->with('foo')->andReturn($entry = m::mock('Basset\Manifest\Entry'));
        $this->manifest->shouldReceive('forget')->once()->with('foo');

        $entry->shouldReceive('hasProductionFingerprints')->once()->andReturn(false);
        $this->files->shouldReceive('glob')->with('path/to/builds/foo-*.*')->andReturn(array('path/to/builds/foo-123.css'));
        $this->files->shouldReceive('delete')->with('path/to/builds/foo-123.css');
        $entry->shouldReceive('resetProductionFingerprints')->once();

        $entry->shouldReceive('hasDevelopmentAssets')->once()->andReturn(false);
        $this->files->shouldReceive('deleteDirectory')->once()->with('path/to/builds/foo');
        $entry->shouldReceive('resetDevelopmentAssets')->once();

        $this->cleaner->clean('foo');
    }


}