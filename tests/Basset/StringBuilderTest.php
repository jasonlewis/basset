<?php

use Mockery as m;
use Basset\Builder\StringBuilder;

class StringBuilderTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testCollectionsAreBuiltWithStringBuilder()
    {
        $assets = array(
            $this->getAssetMock(),
            $this->getAssetMock()
        );
        $assets[0]->shouldReceive('isIgnored')->once()->andReturn(false);
        $assets[0]->shouldReceive('isRemote')->once()->andReturn(false);
        $assets[0]->shouldReceive('getRelativePath')->once()->andReturn('foo.css');
        $assets[0]->shouldReceive('build')->once()->andReturn('body { background-color: #fff; }');
        $assets[1]->shouldReceive('isIgnored')->once()->andReturn(false);
        $assets[1]->shouldReceive('isRemote')->once()->andReturn(false);
        $assets[1]->shouldReceive('getRelativePath')->once()->andReturn('bar.css');
        $assets[1]->shouldReceive('build')->once()->andReturn('a { text-decoration: none; }');

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('processCollection')->once();
        $collection->shouldReceive('getAssets')->once()->with('styles')->andReturn($assets);
        $config = $this->getConfigMock();
        $files = $this->getFilesMock();

        $builder = new StringBuilder($files, $config);

        $expectedArray = array(
            'foo.css' => 'body { background-color: #fff; }',
            'bar.css' => 'a { text-decoration: none; }'
        );

        $this->assertEquals($expectedArray, $builder->build($collection, 'styles'));
    }


    /**
     * @expectedException Basset\Exception\EmptyResponseException
     */
    public function testStringBuilderWithIgnoredAssets()
    {
        $asset = $this->getAssetMock();
        $asset->shouldReceive('isIgnored')->once()->andReturn(true);

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('processCollection')->once();
        $collection->shouldReceive('getAssets')->once()->with('styles')->andReturn(array($asset));
        $collection->shouldReceive('getName')->once()->andReturn('foo');
        $config = $this->getConfigMock();
        $files = $this->getFilesMock();

        $builder = new StringBuilder($files, $config);

        $builder->build($collection, 'styles');
    }


    /**
     * @expectedException Basset\Exception\EmptyResponseException
     */
    public function testStringBuilderWithRemoteAssetsAndNoRemoteCompiling()
    {
        $asset = $this->getAssetMock();
        $asset->shouldReceive('isIgnored')->once()->andReturn(false);
        $asset->shouldReceive('isRemote')->once()->andReturn(true);

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('processCollection')->once();
        $collection->shouldReceive('getAssets')->once()->with('styles')->andReturn(array($asset));
        $collection->shouldReceive('getName')->once()->andReturn('foo');
        $config = $this->getConfigMock();
        $config->shouldReceive('get')->once()->with('basset::build_remotes', false)->andReturn(false);
        $files = $this->getFilesMock();

        $builder = new StringBuilder($files, $config);

        $builder->build($collection, 'styles');
    }


    protected function getCollectionMock()
    {
        return m::mock('Basset\Collection');
    }


    protected function getAssetMock()
    {
        return m::mock('Basset\Asset');
    }


    protected function getConfigMock()
    {
        return m::mock('Illuminate\Config\Repository');
    }


    protected function getFilesMock()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }


}