<?php

use Mockery as m;
use Basset\Console\BuildCommand;

class BuildCommandTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testBuilderCallsScriptAndStyleBuildMethods()
    {
        $collections = array(
            m::mock('Basset\Collection'),
            m::mock('Basset\Collection')
        );
        $collections[0]->shouldReceive('getName')->twice()->andReturn('foo');
        $collections[1]->shouldReceive('getName')->twice()->andReturn('qux');

        $basset = m::mock('Basset\Basset');
        $basset->shouldReceive('getCollections')->once()->andReturn($collections);
        $builder = m::mock('Basset\Builder\BuilderInterface');
        $builder->shouldReceive('setBuildPath')->with('path/to/build');
        $builder->shouldReceive('buildStyles')->once()->with($collections[0]);
        $builder->shouldReceive('buildStyles')->once()->with($collections[1]);
        $builder->shouldReceive('buildScripts')->once()->with($collections[0]);
        $builder->shouldReceive('buildScripts')->once()->with($collections[1]);
        $builder->shouldReceive('getFingerprint')->once()->andReturn(md5('bar'));
        $builder->shouldReceive('getFingerprint')->once()->andReturn(md5('baz'));
        $manifest = m::mock('Basset\Manifest\Repository');
        $manifest->shouldReceive('register')->once()->with($collections[0], md5('bar'), null);
        $manifest->shouldReceive('register')->once()->with($collections[1], md5('baz'), null);
        $cleaner = m::mock('Basset\BuildCleaner');
        $cleaner->shouldReceive('clean')->once();

        $command = new BuildCommand($basset, $builder, $manifest, $cleaner, 'path/to/build');

        $this->runCommand($command);
    }


    public function testBuilderCallsDevelopmentMethods()
    {
        $collections = array(
            m::mock('Basset\Collection'),
            m::mock('Basset\Collection')
        );
        $collections[0]->shouldReceive('getName')->twice()->andReturn('foo');
        $collections[1]->shouldReceive('getName')->twice()->andReturn('qux');

        $basset = m::mock('Basset\Basset');
        $basset->shouldReceive('getCollections')->once()->andReturn($collections);
        $builder = m::mock('Basset\Builder\BuilderInterface');
        $builder->shouldReceive('setBuildPath')->with('path/to/build');
        $builder->shouldReceive('buildDevelopment')->once()->with($collections[0], 'styles');
        $builder->shouldReceive('buildDevelopment')->once()->with($collections[1], 'styles');
        $builder->shouldReceive('buildDevelopment')->once()->with($collections[0], 'scripts');
        $builder->shouldReceive('buildDevelopment')->once()->with($collections[1], 'scripts');
        $builder->shouldReceive('getFingerprint')->once()->andReturn(md5('bar'));
        $builder->shouldReceive('getFingerprint')->once()->andReturn(md5('baz'));
        $manifest = m::mock('Basset\Manifest\Repository');
        $manifest->shouldReceive('register')->once()->with($collections[0], md5('bar'), true);
        $manifest->shouldReceive('register')->once()->with($collections[1], md5('baz'), true);
        $cleaner = m::mock('Basset\BuildCleaner');
        $cleaner->shouldReceive('clean')->once();

        $command = new BuildCommand($basset, $builder, $manifest, $cleaner, 'path/to/build');

        $this->runCommand($command, array('--dev' => true));
    }


    protected function runCommand($command, $input = array(), $output = null)
    {
        $output = $output ?: new Symfony\Component\Console\Output\NullOutput;

        return $command->run(new Symfony\Component\Console\Input\ArrayInput($input), $output);
    }


}