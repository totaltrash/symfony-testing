<?php

namespace Totaltrash\Testing\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Generic test helpers for use in a command context.
 */
abstract class CommandTestCase extends KernelTestCase
{
    /**
     * @var CommandTester
     */
    protected $tester;

    /**
     * Run the given command.
     * 
     * @param string  $name
     * @param Command $commandInstance
     * @param array   $arguments
     */
    public function runCommand($name, Command $commandInstance, $arguments = array())
    {
        $this->application = new Application($this->getKernel());
        $this->application->add($commandInstance);

        $command = $this->application->find($name);
        $tester = new CommandTester($command);
        $tester->execute($arguments);

        $this->getAssert()->setTester($tester);
    }

    /**
     * Get the assert.
     * 
     * @return CommandAssert
     */
    abstract public function getAssert();
}
