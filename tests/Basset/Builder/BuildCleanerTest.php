<?php

use Mockery as m;
use Basset\Manifest\Entry;
use Basset\Manifest\Repository;

class BuildCleanerTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testCleanupOfSingleCollection()
    {
        $cleaner = $this->getBuildCleanerInstance();

        $cleaner->getManifest()->getManifest()->setEntry('foo', new Entry(array(
            'fingerprints' => array(
                'stylesheets' => 'bar',
                'javascripts' => 'baz'
            )
        )));
        
        $files = array();

        $cleaner->shouldReceive('getFilesystemIterator')->once()->with('path/to/build')->andReturn(array(
            $files[] = m::mock('SplFileInfo'),
            $files[] = m::mock('SplFileInfo')
        ));

        $files[0]->shouldReceive('getFilename')->once()->andReturn('foo-qux.css');
        $files[0]->shouldReceive('getPathname')->once()->andReturn('path/to/build/foo-qux.css');
        $files[1]->shouldReceive('getFilename')->once()->andReturn('foo-qux.js');
        $files[1]->shouldReceive('getPathname')->once()->andReturn('path/to/build/foo-qux.js');

        $cleaner->getFiles()->shouldReceive('delete')->once()->with('path/to/build/foo-qux.css');
        $cleaner->getFiles()->shouldReceive('delete')->once()->with('path/to/build/foo-qux.js');

        $cleaner->clean('foo');
    }


    public function testCleanupOfAllCollections()
    {
        $cleaner = $this->getBuildCleanerInstance();

        $cleaner->getManifest()->getManifest()->setEntry('foo', new Entry(array(
            'fingerprints' => array(
                'stylesheets' => '12345',
                'javascripts' => 'abcde'
            )
        )));

        $cleaner->getManifest()->getManifest()->setEntry('bar', new Entry(array(
            'fingerprints' => array(
                'stylesheets' => '54321',
                'javascripts' => 'edcba'
            )
        )));
        
        $files = array();

        $cleaner->shouldReceive('getFilesystemIterator')->twice()->with('path/to/build')->andReturn(array(
            $files[] = m::mock('SplFileInfo'),
            $files[] = m::mock('SplFileInfo')
        ), array(
            $files[] = m::mock('SplFileInfo')
        ));

        $files[0]->shouldReceive('getFilename')->once()->andReturn('foo-12345.css');
        $files[1]->shouldReceive('getFilename')->once()->andReturn('foo-qux.js');
        $files[1]->shouldReceive('getPathname')->once()->andReturn('path/to/build/foo-qux.js');
        $files[2]->shouldReceive('getFilename')->once()->andReturn('bar-qux.js');
        $files[2]->shouldReceive('getPathname')->once()->andReturn('path/to/build/bar-qux.js');

        $cleaner->getFiles()->shouldReceive('delete')->once()->with('path/to/build/foo-qux.js');
        $cleaner->getFiles()->shouldReceive('delete')->once()->with('path/to/build/bar-qux.js');

        $cleaner->clean();
    }


    protected function getBuildCleanerInstance()
    {
        return m::mock('Basset\BuildCleaner', array($this->getManifestInstance(), $this->getFilesMock(), 'path/to/build'))->shouldDeferMissing();
    }


    protected function getCollectionMock()
    {
        return m::mock('Basset\Collection');
    }


    protected function getManifestInstance()
    {
        return new Repository($this->getFilesMock(), 'path/to/meta');
    }


    protected function getEntryMock()
    {
        return m::mock('Basset\Manifest\Entry');
    }


    protected function getFilesMock()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }


}