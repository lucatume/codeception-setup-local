<?php

namespace tad\Codeception\Command;


use Codeception\Configuration;
use Codeception\CustomCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Yaml\Yaml;
use tad\WPBrowser\Filesystem\Filesystem;

class SetupScaffold extends Command implements CustomCommandInterface
{
	const SLUG = 'util:setup-scaffold';

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct($name = null, Filesystem $filesystem = null)
    {
        parent::__construct($name);
        $this->filesystem = $filesystem ? $filesystem : new Filesystem();
    }

    protected function configure()
    {
        $this->setName('setup:scaffold')
            ->addOption('destination', 'd', InputOption::VALUE_OPTIONAL, 'If set the scaffold file will be written to the specified destination', false)
            ->addOption('yes', null, InputOption::VALUE_OPTIONAL, 'If set any confirmation the command requires will be set to affirmative', false)
            ->addOption('skip-suites', null, InputOption::VALUE_OPTIONAL, 'If set the command will not create a distribution version of the suites configuration files.', false);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Configuration::config();

        $setupFilePath = $this->getSetupFilePath($input);

        $localCodeceptionConfig = codecept_root_dir('codeception.yml');

        $this->ensureCodeceptionLocalConfigExists($localCodeceptionConfig);

        $distCodeceptionConfig = codecept_root_dir('codeception.dist.yml');

        if (!$this->filesystem->file_exists($distCodeceptionConfig)) {
            $this->writeCodeceptionConfigDistFile($output, $distCodeceptionConfig, $localCodeceptionConfig);
        }

        $config = $this->readCodeceptionConfig($localCodeceptionConfig);

        $testsDirectory = $this->getTestsDirectory($config);

        if (!$this->filesystem->file_exists($testsDirectory)) {
            throw new RuntimeException('Expected tests directory [' . $testsDirectory . '] not found');
        }

        $testDirectoryFiles = $this->getSuitesFileIterator($testsDirectory);

        if (!$input->getOption('skip-suites')) {
            $this->createSuiteConfigsDistFiles($input, $output, $testDirectoryFiles);
        }

        $setupFileLines = $this->getSetupFileSuitesLines($testDirectoryFiles);

        $this->writeSetupFile($setupFilePath, $setupFileLines);

        $output->writeln('<info>Setup file written to [' . $setupFilePath . ']</info>');
        $output->writeln('<info>You can run the setup command immediately using the `wpcept setup` command.</info>');
    }

    /**
     * @return array
     */
    protected function getSetupFileHeaderLines()
    {
        $projectName = implode(' ', array_map('ucfirst', preg_split('/[-_\\s]+/', basename(codecept_root_dir()))));

        return <<< YAML
# $projectName project local setup instructions
#
# ================================================

# To setup your local testing environment make sure Composer is installed and install WPBrowser running:
# 
#   composer install
# 
# from the command line.
# To execute all the sections setup instructions run:
# 
#   wpcept setup
# 
# To execute one setup section only, e.g. the "acceptance" one, run:
# 
#   wpcept setup --section=acceptance
# 
#  Happy testing!
#
# ================================================
#
# Need to modify/update the setup instructions below?
# Refer to the `setup` command instructions at https://github.com/lucatume/codeception-setup-local#setup
#
# ================================================
YAML;
    }

    /**
     * @param $testsDirectory
     * @return \RegexIterator
     */
    protected function getSuitesFileIterator($testsDirectory)
    {
        $testDirectoryFiles = new \RegexIterator(new \FilesystemIterator($testsDirectory), '/.*\\.suite\\.yml$/');
        return $testDirectoryFiles;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param $testDirectoryFiles
     * @return int|string
     */
    protected function createSuiteConfigsDistFiles(InputInterface $input, OutputInterface $output, $testDirectoryFiles)
    {
        $question = $this->getHelper('question');

        foreach ($testDirectoryFiles as $suiteConfigFile => $fileInfo) {
            $suiteConfigFileBasename = basename($suiteConfigFile, '.suite.yml');

            $suiteDistFilePath = str_replace('.suite.yml', '.suite.dist.yml', $suiteConfigFile);
            $suiteDistFileName = $suiteDistFilePath;

            if ($this->filesystem->file_exists($suiteDistFileName)) {
                $output->writeln('<info>A distribution version of the [' . $suiteConfigFileBasename . '] suite config file already exists, skipping.</info>');
                continue;
            }

            if (!$input->getOption('yes')) {
                $confirmation = new ConfirmationQuestion('Create a distribution version of the [' . $suiteConfigFileBasename . '] file?', true);

                if (!$question->ask($input, $output, $confirmation)) {
                    continue;
                }
            }

            $this->filesystem->file_put_contents($suiteDistFilePath, $this->filesystem->file_get_contents($suiteConfigFile));
            $output->writeln('<info>Distribution version of [' . $suiteConfigFileBasename . '] suite created in [' . $suiteDistFilePath . ']</info>');
        }
    }

    /**
     * @param OutputInterface $output
     * @param $distCodeceptionConfig
     * @param $localCodeceptionConfig
     * @return array
     */
    protected function writeCodeceptionConfigDistFile(OutputInterface $output, $distCodeceptionConfig, $localCodeceptionConfig)
    {
        $output->writeln('<info>Creating distribution version of [codeception.yml] file in [codeception.dist.yml] file.</info>');
        $this->filesystem->file_put_contents($distCodeceptionConfig, $this->filesystem->file_get_contents($localCodeceptionConfig));
    }

    /**
     * @param $localCodeceptionConfig
     * @return mixed
     */
    protected function readCodeceptionConfig($localCodeceptionConfig)
    {
        $config = Yaml::parse($this->filesystem->file_get_contents($localCodeceptionConfig));
        return $config;
    }

    /**
     * @param $config
     * @return string
     */
    protected function getTestsDirectory($config)
    {
        $testsDirectory = empty($config['paths']['tests']) ? codecept_root_dir('tests') : codecept_root_dir($config['paths'] ['tests']);
        return $testsDirectory;
    }

    /**
     * @param $testDirectoryFiles
     * @return mixed
     */
    protected function getSetupFileSuitesLines($testDirectoryFiles)
    {
        $setupFileLines = [];
        foreach ($testDirectoryFiles as $suiteConfigFile => $fileInfo) {
            $suiteName = basename($suiteConfigFile, '.suite.yml');
            $suitePrettyName = ucfirst($suiteName);
            $setupFileLines[$suiteName] = ['message' => ['value' => 'Answer some questions to configure the ' . $suitePrettyName . ' suite.']];
        }
        return $setupFileLines;
    }

    /**
     * @param $file
     * @param $setupFileLines
     */
    protected function writeSetupFile($file, $setupFileLines)
    {
        $header = $this->getSetupFileHeaderLines();
        $yamlDump = Yaml::dump($setupFileLines, 100);

        $written = $this->filesystem->file_put_contents($file, $header . "\n\n" . $yamlDump);

        if ($written === false) {
            throw new RuntimeException('Could not write setup scaffold file to [' . $file . ']');
        }
    }

    /**
     * @param $localCodeceptionConfig
     */
    protected function ensureCodeceptionLocalConfigExists($localCodeceptionConfig)
    {
        if (!$this->filesystem->file_exists($localCodeceptionConfig)) {
            throw new RuntimeException('Bootstrap and configure your codeception locally before running the setup:scaffold command.');
        }
    }

    /**
     * @param InputInterface $input
     * @return mixed|string
     */
    protected function getSetupFilePath(InputInterface $input)
    {
        $destination = $input->getOption('destination');
        $file = $destination ? $destination : codecept_root_dir('setup.yml');
        return $file;
    }

	/**
	 * returns the name of the command
	 *
	 * @return string
	 */
	public static function getCommandName()
	{
		return 'setup:scaffold';
	}
}
