<?php

namespace tad\Codeception\Command\SetupLocal\Instructions;


class ExecInstruction extends AbstractInstruction implements InstructionInterface
{

    public function execute()
    {
        $go = $this->getGo();

        if (!$go) {
            return $this->vars;
        }

        $scriptString = is_array($this->value) ? $this->value['value'] : $this->value;
        exec($this->replaceVarsInString($scriptString));

        return $this->vars;
    }
}