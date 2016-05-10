<?php

namespace tad\Codeception\Command\SetupLocal\Instructions;


class MessageInstruction extends AbstractInstruction implements InstructionInterface
{

    public function execute()
    {
        $go = $this->getGo();
        
        if (!$go) {
            return $this->vars;
        }

        $value = is_array($this->value) ? $this->value['value'] : $this->value;

        $message = $this->replaceVarsInString($value);
        $this->output->writeln('<info>' . $message . '</info>');
        
        return $this->vars;
    }
}