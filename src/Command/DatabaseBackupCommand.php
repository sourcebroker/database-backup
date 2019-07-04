<?php

namespace SourceBroker\DatabaseBackup\Command;

use Cron\CronExpression;
use Exception;
use SourceBroker\DatabaseBackup\Configuration\CommandConfiguration;
use SourceBroker\DatabaseBackup\Configuration\ConfigurationMerger;
use SourceBroker\DatabaseBackup\DatabaseAccess\ProviderInterface;
use SourceBroker\DatabaseBackup\Service\DatabaseBackupService;
use SourceBroker\DatabaseBackup\Service\PathService;
use SourceBroker\DatabaseBackup\Storage\StorageInterface;
use Symfony\Component\Config\Definition\Processor;

use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class DatabaseBackupCommand
 */
class DatabaseBackupCommand extends BaseCommand
{

    /**
     * @var ProviderInterface[]
     */
    protected $databaseAccessProviders = [];

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('db:dump')
            ->setDescription('Create database backup')
            ->addArgument(
                'yaml',
                InputArgument::REQUIRED,
                'YAML file name with configuration'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Run command to check configuration'
            );
    }

    protected function afterInitialize(InputInterface $input, OutputInterface $output)
    {
        $input->validate();
        $output->writeln("<info>➤</info> Reading configuration from <comment>{$input->getArgument('yaml')}</comment>");

        $mergedConfigArray = array_replace_recursive(
            [
                'defaults' => $this->defaultConfiguration
            ],
            $this->readYamlConfiguration($input->getArgument('yaml'))
        );

        $configurationMerger = new ConfigurationMerger();
        $mergedConfigArray = $configurationMerger->process($mergedConfigArray);

        $processor = new Processor();
        $configuration = new CommandConfiguration($this->container);
        $this->config = $processor->processConfiguration($configuration, ['configuration' => $mergedConfigArray]);

        $this->checkDatabaseAccess($this->config[CommandConfiguration::KEY_DEFAULTS]);
        foreach ($this->config[CommandConfiguration::KEY_CONFIGS] as $configKey => &$config) {
            $this->checkDatabaseAccess($config, $configKey);
        }

        parent::afterInitialize($input, $output);
    }

    /**
     * @param $path
     * @return mixed
     */
    protected function readYamlConfiguration($path)
    {
        if (empty($realPath = $this->container->get(PathService::class)->checkIfFileOrDirectoryExist($path))) {
            throw new FileNotFoundException(null, 0, null, $path);
        }
        return Yaml::parseFile($realPath);
    }

    /**
     * @param $data
     * @param null $key
     */
    protected function checkDatabaseAccess(&$data, $key = null)
    {
        if (isset($data['databaseAccess'])) {
            /** @var ProviderInterface $provider */
            $provider = $this->container->get('database_access')->getInstance($data, $key);
            $newPath = $provider->process();
            if ($newPath) {
                $this->databaseAccessProviders[] = $provider;
                $data['defaultsFile'] = $newPath;
            }
        }
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
        foreach ($this->config['configs'] as $key => $config) {
            $toRun = CronExpression::factory((string)$config['cron']['pattern'])->isDue();

            if (!empty($config[CommandConfiguration::KEY_CRON][CommandConfiguration::KEY_CRON_ON_DEMAND])) {
                $toRun &= $this->checkOnDemandFlag($key);
            }

            if ($toRun) {
                $output->writeln("<info>➤</info> Executing database backup for key <comment>{$key}</comment>");

                if (!empty($config[CommandConfiguration::KEY_CRON][CommandConfiguration::KEY_CRON_ON_DEMAND])) {
                    $output->writeln("<info>➤</info> Remove <comment>on demand</comment> flag");
                    $this->deleteOnDemandFlag($key);
                }

                if ($input->getOption('dry-run') === false) {
                    $paths = $backupService = $this->container
                        ->get(DatabaseBackupService::class)
                        ->setKey($key)
                        ->setConfig($config)
                        ->execute();

                    foreach ($config['storage'] as $storageName => $items) {
                        foreach ($items as $storageSettings) {
                            if ($this->container->has($storageName . '_storage')) {
                                /** @var StorageInterface $storage */
                                $storage = $this->container->get($storageName . '_storage');
                                $storage
                                    ->setSettings($storageSettings)
                                    ->setKey($key)
                                    ->setGlobalConfig($config)
                                    ->save($paths);
                            } else {
                                $output->writeln(sprintf('<error>Missing storage service for %s<error>', $storageName));
                            }
                        }
                    }

                    foreach ($paths as $path) {
                        unlink($path);
                    }
                }
            }
        }

        foreach ($this->databaseAccessProviders as $provider) {
            $provider->clean();
        }
    }

    /**
     * Checks should "on demand" backup mode should be executed.
     * This condition depends on existance of flag in defined directory.
     *
     * @param string $backupMode
     * @return bool
     */
    protected function checkOnDemandFlag(string $backupMode): bool
    {
        $path = $this->config[CommandConfiguration::KEY_CONFIGS][$backupMode][CommandConfiguration::KEY_FLAG_DIR]
            . DIRECTORY_SEPARATOR. strtolower($backupMode);
        return file_exists($path);
    }

    /**
     * Delete "on demand" flag for given backup mode.
     *
     * @param string $backupMode
     */
    protected function deleteOnDemandFlag(string $backupMode)
    {
        $path = $this->config[CommandConfiguration::KEY_CONFIGS][$backupMode][CommandConfiguration::KEY_FLAG_DIR]
            . DIRECTORY_SEPARATOR . strtolower($backupMode);
        if (file_exists($path)) {
            unlink($path);
        }
    }

}
