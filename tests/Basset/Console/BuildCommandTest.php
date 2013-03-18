<?php

use Mockery as m;
use Basset\Console\BuildCommand;

class BuildCommandTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testBuilderCallsJavascriptAndStylesheetBuildMethods()
    {
        $collections = array(
            m::mock('Basset\Collection'),
            m::mock('Basset\Collection')
        );
        $collections[0]->shouldReceive('getName')->twice()->andReturn('foo');
        $collections[1]->shouldReceive('getName')->twice()->andReturn('qux');

        $env = m::mock('Basset\Environment');
        $env->shouldReceive('getCollections')->once()->andReturn($collections);
        $builder = m::mock('Basset\Builder\BuilderInterface');
        $builder->shouldReceive('setBuildPath')->with('path/to/build');
        $builder->shouldReceive('build')->once()->with($collections[0], 'stylesheets', null);
        $builder->shouldReceive('build')->once()->with($collections[1], 'stylesheets', null);
        $builder->shouldReceive('build')->once()->with($collections[0], 'javascripts', null);
        $builder->shouldReceive('build')->once()->with($collections[1], 'javascripts', null);
        $builder->shouldReceive('getFingerprint')->once()->andReturn(md5('bar'));
        $builder->shouldReceive('getFingerprint')->once()->andReturn(md5('baz'));
        $manifest = m::mock('Basset\Manifest\Repository');
        $manifest->shouldReceive('register')->once()->with($collections[0], md5('bar'), null);
        $manifest->shouldReceive('register')->once()->with($collections[1], md5('baz'), null);
        $cleaner = m::mock('Basset\BuildCleaner');
        $cleaner->shouldReceive('clean')->once();

        $command = new BuildCommand($env, $builder, $manifest, $cleaner, 'path/to/build');

        $this->runCommand($command);
    }


    protected function runCommand($command, $input = array(), $output = null)
    {
        $output = $output ?: new Symfony\Component\Console\Output\NullOutput;

        return $command->run(new Symfony\Component\Console\Input\ArrayInput($input), $output);
    }


}