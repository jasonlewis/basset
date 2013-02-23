<?php

use Mockery as m;
use JasonLewis\Basset\Console\CompileCommand;

class CompileCommandTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testCompilerCallsScriptAndStyleCompileMethods()
    {
        $collections = array(
            m::mock('JasonLewis\Basset\Collection'),
            m::mock('JasonLewis\Basset\Collection')
        );
        $collections[0]->shouldReceive('getName')->twice()->andReturn('foo');
        $collections[1]->shouldReceive('getName')->twice()->andReturn('qux');

        $basset = m::mock('JasonLewis\Basset\Basset');
        $basset->shouldReceive('getCollections')->once()->andReturn($collections);
        $compiler = m::mock('JasonLewis\Basset\Compiler\CompilerInterface');
        $compiler->shouldReceive('setCompilePath')->with('path/to/compile');
        $compiler->shouldReceive('compileStyles')->once()->with($collections[0]);
        $compiler->shouldReceive('compileStyles')->once()->with($collections[1]);
        $compiler->shouldReceive('compileScripts')->once()->with($collections[0]);
        $compiler->shouldReceive('compileScripts')->once()->with($collections[1]);
        $compiler->shouldReceive('getFingerprint')->once()->andReturn(md5('bar'));
        $compiler->shouldReceive('getFingerprint')->once()->andReturn(md5('baz'));
        $repository = m::mock('JasonLewis\Basset\CollectionRepository');
        $repository->shouldReceive('load')->once();
        $repository->shouldReceive('register')->once()->with($collections[0], md5('bar'), null);
        $repository->shouldReceive('register')->once()->with($collections[1], md5('baz'), null);

        $command = new CompileCommand($basset, $compiler, $repository, 'path/to/compile');

        $this->runCommand($command);
    }


    public function testCompilerCallsDevelopmentMethods()
    {
        $collections = array(
            m::mock('JasonLewis\Basset\Collection'),
            m::mock('JasonLewis\Basset\Collection')
        );
        $collections[0]->shouldReceive('getName')->twice()->andReturn('foo');
        $collections[1]->shouldReceive('getName')->twice()->andReturn('qux');

        $basset = m::mock('JasonLewis\Basset\Basset');
        $basset->shouldReceive('getCollections')->once()->andReturn($collections);
        $compiler = m::mock('JasonLewis\Basset\Compiler\CompilerInterface');
        $compiler->shouldReceive('setCompilePath')->with('path/to/compile');
        $compiler->shouldReceive('compileDevelopment')->once()->with($collections[0], 'styles');
        $compiler->shouldReceive('compileDevelopment')->once()->with($collections[1], 'styles');
        $compiler->shouldReceive('compileDevelopment')->once()->with($collections[0], 'scripts');
        $compiler->shouldReceive('compileDevelopment')->once()->with($collections[1], 'scripts');
        $compiler->shouldReceive('getFingerprint')->once()->andReturn(md5('bar'));
        $compiler->shouldReceive('getFingerprint')->once()->andReturn(md5('baz'));
        $repository = m::mock('JasonLewis\Basset\CollectionRepository');
        $repository->shouldReceive('load')->once();
        $repository->shouldReceive('register')->once()->with($collections[0], md5('bar'), true);
        $repository->shouldReceive('register')->once()->with($collections[1], md5('baz'), true);

        $command = new CompileCommand($basset, $compiler, $repository, 'path/to/compile');

        $this->runCommand($command, array('--dev' => true));
    }


    protected function runCommand($command, $input = array(), $output = null)
    {
        $output = $output ?: new Symfony\Component\Console\Output\NullOutput;

        return $command->run(new Symfony\Component\Console\Input\ArrayInput($input), $output);
    }


}