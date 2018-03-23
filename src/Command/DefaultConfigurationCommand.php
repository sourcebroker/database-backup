<?php

namespace SourceBroker\DatabaseBackup\Command;

use Exception;
use SourceBroker\DatabaseBackup\Configuration\CommandConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DefaultConfigurationCommand extends BaseCommand
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('db:default-configuration')
            ->setDescription('Get default configuration')
            ->addOption(
                'key',
                null,
                InputOption::VALUE_OPTIONAL,
                'Get value by key'
            )
        ;
    }

    protected function afterInitialize(InputInterface $input, OutputInterface $output)
    {
        $processor = new Processor();
        $configuration = new CommandConfiguration($this->container, false);
        $mergedConfigArray = [
            'defaults' => $this->defaultConfiguration
        ];

        $this->config = $processor->processConfiguration($configuration, ['configuration' => $mergedConfigArray]);
    }

    /**
     *
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return null|int null or 0 if everything went fine, or an error code
     *
     * @throws LogicException When this abstract method is not implemented
     * @throws Exception When this abstract method is not implemented
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('key')) {
            $value = $this->config['defaults'][$input->getOption('key')] ?? [];

        } else {
            $value = $this->config['defaults'];
        }

        $output->writeln(is_array($value) ? json_encode($value) : $value);
    }
}