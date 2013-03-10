<?php

use Mockery as m;
use Basset\Console\CleanCommand;

class CleanCommandTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testCleanCommand()
    {
        $cleaner = m::mock('Basset\BuildCleaner');
        $cleaner->shouldReceive('clean')->once()->with(null)->andReturn(true);
        $cleaner->shouldReceive('clean')->once()->with('foo')->andReturn(true);

        $command = new CleanCommand($cleaner);

        $this->runCommand($command);
        $this->runCommand($command, array('collection' => 'foo'));
    }


    protected function runCommand($command, $input = array(), $output = null)
    {
        $output = $output ?: new Symfony\Component\Console\Output\NullOutput;

        return $command->run(new Symfony\Component\Console\Input\ArrayInput($input), $output);
    }


}