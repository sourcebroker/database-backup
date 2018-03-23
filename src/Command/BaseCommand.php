<?php

namespace SourceBroker\DatabaseBackup\Command;

use SourceBroker\DatabaseBackup\DatabaseAccess\Builder;
use SourceBroker\DatabaseBackup\Service\DatabaseBackupService;
use SourceBroker\DatabaseBackup\Service\PathService;
use SourceBroker\DatabaseBackup\Storage\LocalStorage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BaseCommand
 */
abstract class BaseCommand extends Command
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $config;

    protected $defaultConfiguration = [
        'tmpDir' => '.tmp',

        'defaultsFile' => '~/.my.cnf',

        'binaryDbCommand' => '',
        'binaryDbExport' => '',
        'binaryPacker' => '',

        'databaseAccess' => [
            'type' => 'default',
            'path' => '',
            'data' => [
                'user' => '',
                'password' => '',
                'port' => '',
                'host' => '',
            ],
        ],

        'storage' => [
            'local' => [
                [
                    'path' => '.dump'
                ]
            ]
        ],

        'application' => [
            'typo3' => [
                'tables' => [
                    'detection' => [
                        'tt_content'
                    ],
                    'whitelist' => [
                        '.*'
                    ],
                    'blacklist' => [
                        'cf_.*'
                    ],
                    'whitelistPresets' => [],
                    'blacklistPresets' => []
                ]
            ],
            'magento' => [
                'tables' => [
                    'detection' => [
                        'core_config_data'
                    ],
                    'whitelist' => [
                        '.*'
                    ],
                    'blacklist' => [
                        '/^cache.*$/',
                        '/^log_.*$/'
                    ],
                    'whitelistPresets' => [],
                    'blacklistPresets' => []
                ]
            ],
        ],
        'tables' => [],
        'databases' => [
            'whitelist' => [
                '.*'
            ],
            'blacklist' => [
                'information_schema'
            ],
            'whitelistPresets' => [],
            'blacklistPresets' => []
        ],
        'presets' => []
    ];

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->container = new ContainerBuilder();

        $this->container
            ->register(PathService::class, PathService::class)
        ;

        $this->container
            ->register(DatabaseBackupService::class, DatabaseBackupService::class)
            ->addArgument($this->container)
        ;

        $this->container
            ->register('local_storage', LocalStorage::class)
        ;

        $this->container
            ->register('database_access', Builder::class)
            ->setArgument('container', $this->container)
        ;

        $this->afterInitialize($input, $output);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function afterInitialize(InputInterface $input, OutputInterface $output){}
}