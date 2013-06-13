<?php

use Mockery as m;
use Basset\AssetPublisher;
use org\bovigo\vfs\vfsStream;

class AssetPublisherTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function setUp()
	{
		$this->files = new Illuminate\Filesystem\Filesystem;
		$this->config = m::mock('Illuminate\Config\Repository');
		$this->vfs = vfsStream::setup('example', 0755, array(
			'public' => array(),
			'vendor' => array(
				'foo' => array(
					'bar' => array(
						'css' => array('baz.css' => 'body { background-color: #fff; }')
					)
				)
			),
			'assets' => array(
				'css' => array('qux.css' => 'body { background-color: #000; }')
			)
		));

		$publicPath = $this->vfs->getChild('public')->url();
		$basePath = $this->vfs->url();
		$publishPath = $this->vfs->getChild('public')->url();

		$this->publisher = new AssetPublisher($this->files, $this->config, $publicPath, $basePath, $publishPath);
	}


	public function testPublishingVendorFile()
	{
		$published = $this->publisher->publish(array(vfsStream::url('example/vendor/foo/bar/css/baz.css')));

		$this->assertEquals(array('vendor/foo/bar/css/baz.css' => 'public/foo/bar/css/baz.css'), $published);
	}


	public function testPublishingVendorDirectory()
	{
		$published = $this->publisher->publish(array(vfsStream::url('example/vendor/foo/bar/css')));

		$this->assertEquals(array('vendor/foo/bar/css' => 'public/foo/bar/css'), $published);
	}


	public function testPublishingNonVendorFile()
	{
		$published = $this->publisher->publish(array(vfsStream::url('example/assets/css/qux.css')));

		$this->assertEquals(array('assets/css/qux.css' => 'public/assets/css/qux.css'), $published);
	}


	public function testPublishingNonVendorDirectory()
	{
		$published = $this->publisher->publish(array(vfsStream::url('example/assets/css')));

		$this->assertEquals(array('assets/css' => 'public/assets/css'), $published);
	}


	public function testPublishingExistingVendorDirectoryWithChangedDirectoryStructure()
	{
		vfsStream::newFile('bam.css')->withContent('div.foo { }')->at($this->vfs->getChild('vendor/foo/bar/css'));

		vfsStream::newDirectory('foo/bar/css')->at($this->vfs->getChild('public'));
		$this->vfs->getChild('vendor/foo/bar/css/baz.css')->at($this->vfs->getChild('public/foo/bar/css'));

		$published = $this->publisher->publish(array($this->vfs->getChild('vendor/foo/bar/css')->url()));

		$this->assertEquals(array('vendor/foo/bar/css' => 'public/foo/bar/css'), $published);
	}


	public function testPublishingExistingVendorDirectoryWithUnchangedDirectoryStructure()
	{
		vfsStream::newDirectory('foo/bar/css')->at($this->vfs->getChild('public'));
		$this->vfs->getChild('vendor/foo/bar/css/baz.css')->at($this->vfs->getChild('public/foo/bar/css'));

		$published = $this->publisher->publish(array($this->vfs->getChild('vendor/foo/bar/css')->url()));

		$this->assertEmpty($published);
	}


	public function testPublishingExistingVendorFileWithChanges()
	{
		vfsStream::newDirectory('foo/bar/css')->at($this->vfs->getChild('public'));
		vfsStream::newFile('baz.css')->withContent('div.foo { }')->at($this->vfs->getChild('public/foo/bar/css'));

		$published = $this->publisher->publish(array($this->vfs->getChild('vendor/foo/bar/css/baz.css')->url()));

		$this->assertEquals(array('vendor/foo/bar/css/baz.css' => 'public/foo/bar/css/baz.css'), $published);
	}


	public function testPublishingExistingVendorFileWithNoChanges()
	{
		vfsStream::newDirectory('foo/bar/css')->at($this->vfs->getChild('public'));
		vfsStream::newFile('baz.css')->withContent('body { background-color: #fff; }')->at($this->vfs->getChild('public/foo/bar/css'));

		$published = $this->publisher->publish(array($this->vfs->getChild('vendor/foo/bar/css/baz.css')->url()));

		$this->assertEmpty($published);
	}


}