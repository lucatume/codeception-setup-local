<?php

namespace tad\Codeception\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

abstract class BaseCommand extends Command
{

    public function getLocalConfigFilePath()
    {
        return codecept_root_dir('commands-config.yml');
    }

    protected function configure()
    {
        $this->addOption('save-config', null, InputOption::VALUE_OPTIONAL, 'If set any option argument will be saved to the local command configuration file.', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (empty($input->getOption('save-config'))) {
            return true;
        }

        $configFilePath = $this->getLocalConfigFilePath();
        $config = file_exists($configFilePath) ? Yaml::parse(file_get_contents($configFilePath)) : [];

        $commandName = $this->getName();

        if (empty($config[$commandName])) {
            $config[$commandName] = [];
        }

        $commandConfig = $config[$commandName];

        $options = array_filter($this->getDefinition()->getOptions(), function (InputOption $option) {
            return $option->getName() !== 'save-config';
        });

        /** @var InputOption $option */
        foreach ($options as $option) {
            $optionName = $option->getName();
            $optionValue = $input->getOption($optionName);
            if (empty($optionValue)) {
                continue;
            }
            $commandConfig[$optionName] = $optionValue;
        }

        $config[$commandName] = $commandConfig;

        $yamlDump = Yaml::dump($config);
        file_put_contents($configFilePath, $yamlDump);

        $output->writeln('<info>[' . $commandName . '] command configuration saved to [' . $configFilePath . '] file.</info>');
    }
}
