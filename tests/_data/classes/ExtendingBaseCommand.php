<?php

namespace tad\Codeception\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExtendingBaseCommand extends BaseCommand
{

    protected function configure()
    {
        $this->setName('dummyExtending');
        $this->addOption('one', null, InputOption::VALUE_OPTIONAL)
            ->addOption('two', null, InputOption::VALUE_OPTIONAL)
            ->addOption('three', null, InputOption::VALUE_OPTIONAL);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
    }
}