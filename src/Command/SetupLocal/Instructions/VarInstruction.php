<?php
namespace tad\Codeception\Command\SetupLocal\Instructions;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class VarInstruction extends AbstractInstruction implements InstructionInterface
{
    protected $validations = [
        'int' => FILTER_VALIDATE_INT,
        'float' => FILTER_VALIDATE_FLOAT,
        'bool' => FILTER_VALIDATE_BOOLEAN,
        'url' => FILTER_VALIDATE_URL,
        'email' => FILTER_VALIDATE_EMAIL,
        'yesno' => FILTER_VALIDATE_REGEXP
    ];

    protected $validationArgs = [
        'yesno' => ['options' => ['regexp' => '/((Y|y)((E|e)(S|s))*|(N|n)(O|o)*)/']]
    ];

    protected $normalizations = [];

    public function __construct($value, array $vars, InputInterface $input, OutputInterface $output, $helper)
    {
        $this->normalizations = [
            'yesno' => [$this, 'normalizeYesNo']
        ];
        parent::__construct($value, $vars, $input, $output, $helper);
    }

    public function execute()
    {
        if (!(isset($this->value['name']) && isset($this->value['question']))) {
            throw new RuntimeException('"name" and "question" are required for the "var" instruction');
        }

        $go = $this->getGo();

        if (!$go) {
            return $this->vars;
        }

        $default = isset($this->value['default']) ? $this->value['default'] : '';
        $defaultMessage = $default ? ' (' . $default . ')' : '';
        $questionText = $this->value['question'] . $defaultMessage;
        $question = new Question($questionText, $default);
        $answer = '';
        while (true) {
            $answer = $this->helper->ask($this->input, $this->output, $question);
            if ($this->validate($answer)) {
                break;
            }
        }
        $this->vars[$this->value['name']] = trim($this->normalizeVar($answer));

        return $this->vars;
    }

    protected function normalizeYesNo($value)
    {
        return preg_match('/^(Y|y)/', $value) ? 'yes' : 'no';
    }

    private function validate($answer)
    {

        if (!(is_array($this->value) && isset($this->value['validate']) && isset($this->validations[$this->value['validate']]))) {
            return true;
        }

        $validationArg = isset($this->validationArgs[$this->value['validate']]) ? $this->validationArgs[$this->value['validate']] : null;

        return filter_var($answer, $this->validations[$this->value['validate']], $validationArg);
    }

    protected function normalizeVar($value)
    {
        return is_array($this->value)
        && isset($this->value['validate'])
        && isset($this->normalizations[$this->value['validate']]) ?
            call_user_func($this->normalizations[$this->value['validate']], $value)
            : $value;
    }
}