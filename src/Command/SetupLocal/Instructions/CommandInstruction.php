<?php

namespace tad\Codeception\Command\SetupLocal\Instructions;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

class CommandInstruction extends AbstractInstruction implements InstructionInterface
{
    /**
     * @var Command
     */
    protected $command;

    public function execute()
    {
        $go = $this->getGo();

        if (!$go) {
            return $this->vars;
        }

        $commandString = is_array($this->value) ? $this->value['value'] : $this->value;
        $commandArgs = explode(' ', $commandString);
        $replacedArrayArgs = array_map([$this, 'replaceVarsInString'], $commandArgs);
        $subCommand = $this->command->getApplication()->find(reset($commandArgs));
        $subCommand->run(new StringInput(implode(' ', $replacedArrayArgs)), $this->output);

        return $this->vars;
    }

    public function __construct($value, array $vars, InputInterface $input, OutputInterface $output, $helper, Command $command)
    {
        parent::__construct($value, $vars, $input, $output, $helper);
        $this->command = $command;
    }
}