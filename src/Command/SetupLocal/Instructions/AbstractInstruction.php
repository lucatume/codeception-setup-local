<?php

namespace tad\Codeception\Command\SetupLocal\Instructions;


use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractInstruction
{
    /**
     * @var array
     */
    protected $vars;
    /**
     * @var string|array
     */
    protected $value;

    /**
     * @var Input
     */
    protected $input;

    /**
     * @var Output
     */
    protected $output;

    /**
     * @var mixed
     */
    protected $helper;

    public function __construct($value, array $vars, InputInterface $input, OutputInterface $output, $helper)
    {
        $this->value = $value;
        $this->vars = $vars;
        $this->input = $input;
        $this->output = $output;
        $this->helper = $helper;
    }

    protected function getGo()
    {
        if (!is_array($this->value)) {
            return true;
        }

        $ifCondition = isset($this->value['if']);
        $unlessCondition = isset($this->value['unless']);

        if ($ifCondition || $unlessCondition) {
            $conditionLine = $ifCondition ? $this->value['if'] : $this->value['unless'];
            $condition = explode(' ', $conditionLine);
            if (count($condition) === 3) {
                $go = $condition[1] === 'is' ? $this->vars[$condition[0]] === $condition[2] : $this->vars[$condition[0]] !== $condition[2];
            } else if (count($condition) === 1) {
                $go = !empty($this->vars[$condition[0]]);
            }

            $go = $unlessCondition ? !$go : $go;

            return $go;
        }

        return true;
    }

    protected function replaceVarsInString($string)
    {
        array_walk($this->vars, function ($value, $key) use (&$string) {
            $string = str_replace('$' . $key, $value, $string);
        });
        return $string;
    }
}