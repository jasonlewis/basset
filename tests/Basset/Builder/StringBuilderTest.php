<?php

use Mockery as m;
use Basset\Builder\StringBuilder;

class StringBuilderTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testStringBuilderBuildsCollection()
    {
        $assets = array(
            $this->getAssetMock(),
            $this->getAssetMock()
        );
        $assets[0]->shouldReceive('isExcluded')->once()->andReturn(false);
        $assets[0]->shouldReceive('getRelativePath')->once()->andReturn('foo.css');
        $assets[0]->shouldReceive('build')->once()->andReturn('body { background-color: #fff; }');
        $assets[1]->shouldReceive('isExcluded')->once()->andReturn(false);
        $assets[1]->shouldReceive('getRelativePath')->once()->andReturn('bar.css');
        $assets[1]->shouldReceive('build')->once()->andReturn('a { text-decoration: none; }');

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('getAssets')->once()->with('styles')->andReturn($assets);

        $builder = new StringBuilder($this->getFilesMock(), $this->getConfigMock());

        $expected = array(
            'foo.css' => 'body { background-color: #fff; }',
            'bar.css' => 'a { text-decoration: none; }'
        );

        $this->assertEquals($expected, $builder->build($collection, 'styles'));
    }


    /**
     * @expectedException Basset\Exception\EmptyResponseException
     */
    public function testStringBuilderWithExcludedAssetsThrowsEmptyResponseException()
    {
        $asset = $this->getAssetMock();
        $asset->shouldReceive('isExcluded')->once()->andReturn(true);

        $collection = $this->getCollectionMock();
        $collection->shouldReceive('getAssets')->once()->with('styles')->andReturn(array($asset));
        $collection->shouldReceive('getName')->once()->andReturn('foo');

        $builder = new StringBuilder($this->getFilesMock(), $this->getConfigMock());

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