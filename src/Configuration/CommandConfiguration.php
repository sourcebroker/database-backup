<?php

namespace SourceBroker\DatabaseBackup\Configuration;

use SourceBroker\DatabaseBackup\Service\PathService;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CommandConfiguration
 */
class CommandConfiguration implements ConfigurationInterface
{
    const KEY_DEFAULTS_FILE = 'defaultsFile';
    const KEY_BINARY_DB_COMMAND = 'binaryDbCommand';
    const KEY_BINARY_DB_EXPORT = 'binaryDbExport';
    const KEY_BINARY_PACKER = 'binaryPacker';
    const KEY_APPLICATION = 'application';
    const KEY_DETECTION = 'detection';
    const KEY_WHITELIST = 'whitelist';
    const KEY_WHITELIST_PRESETS = 'whitelistPresets';
    const KEY_BLACKLIST = 'blacklist';
    const KEY_BLACKLIST_PRESETS = 'blacklistPresets';
    const KEY_APPLICATION_BOOLEAN = 'application';
    const KEY_TABLES = 'tables';
    const KEY_DATABASES = 'databases';
    const KEY_CONFIGS = 'configs';
    const KEY_CRON = 'cron';
    const KEY_CRON_PATTERN = 'pattern';
    const KEY_CRON_HOW_MANY = 'howMany';
    const KEY_STORAGE = 'storage';
    const KEY_STORAGE_LOCAL = 'local';
    const KEY_STORAGE_LOCAL_PATH = 'path';
    const KEY_PRESETS = 'presets';
    const KEY_TMP_DIR = 'tmpDir';
    const KEY_DATABASE_ACCESS = 'databaseAccess';
    const KEY_DATABASE_ACCESS_TYPE = 'type';
    const KEY_DATABASE_ACCESS_PATH = 'path';
    const KEY_DATABASE_ACCESS_DATA = 'data';
    const KEY_DATABASE_ACCESS_DATA_USER = 'user';
    const KEY_DATABASE_ACCESS_DATA_PASSWORD = 'password';
    const KEY_DATABASE_ACCESS_DATA_PORT = 'port';
    const KEY_DATABASE_ACCESS_DATA_HOST = 'host';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var boolean
     */
    protected $configsRequire;

    /**
     * CommandConfiguration constructor.
     * @param ContainerInterface $container
     * @param boolean $configsRequire
     */
    public function __construct(ContainerInterface $container, $configsRequire = true)
    {
        $this->container = $container;
        $this->configsRequire = $configsRequire;
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('configuration');

        $rootNode
            ->children()
                ->arrayNode('defaults')
                    ->addDefaultsIfNotSet()
                    ->append($this->getTmpDir())
                    ->append($this->getDefaultsFile())
                    ->append($this->getDatabaseAccess())
                    ->append($this->getBinaryDbCommand())
                    ->append($this->getBinaryDbExport())
                    ->append($this->getBinaryPacker())
                    ->append($this->getStorage())
                    ->append($this->getApplication())
                    ->append($this->getTables())
                    ->append($this->getDatabases())
                    ->append($this->getPresets())
                ->end()
                ->append($this->getConfigsNode())
            ->end();

        return $treeBuilder;
    }

    protected function getConfigsNode()
    {
        $node = new ArrayNodeDefinition(self::KEY_CONFIGS);
        $node
            ->useAttributeAsKey('name')
            ->beforeNormalization()
            ->always(function ($v) {
                foreach ($v as $key => $item) {
                    $newKey = $this->container->get(PathService::class)->slugify($key, '_');
                    if ($newKey != $key) {
                        $v[$newKey] = $item;
                        unset($v[$key]);
                    }
                }
                return $v;
            })
            ->end()
            ->arrayPrototype()
                ->children()
                    ->append($this->getCron())
                    ->append($this->getTmpDir())
                    ->append($this->getDefaultsFile())
                    ->append($this->getDatabaseAccess())
                    ->append($this->getBinaryDbCommand())
                    ->append($this->getBinaryDbExport())
                    ->append($this->getBinaryPacker())
                    ->append($this->getStorage())
                    ->append($this->getApplication())
                    ->append($this->getTables())
                    ->append($this->getDatabases())
                    ->append($this->getPresets())
                ->end()
            ->end();

        if ($this->configsRequire) {
            $node->isRequired();
        }
        return $node;
    }

    /**
     * @return ArrayNodeDefinition
     */
    protected function getDatabaseAccess()
    {
        $node = new ArrayNodeDefinition(self::KEY_DATABASE_ACCESS);
        $node
            ->validate()
                ->ifTrue(function ($value){
                    return $value['type'] != 'default' && $value['path'] == '';
                })
                ->thenInvalid("This type of database access needs valid path value")
            ->end()
            ->validate()
                ->ifTrue(function ($value){;
                    return $value['type'] != 'default' && $value['data']['user'] == '';
                })
                ->thenInvalid("This type of database access needs valid path to user value [data][user]")
            ->end()
            ->validate()
                ->ifTrue(function ($value){;
                    return $value['type'] != 'default' && $value['data']['password'] == '';
                })
                ->thenInvalid("This type of database access needs valid path to user value [data][password]")
            ->end()
            ->children()
                ->enumNode(self::KEY_DATABASE_ACCESS_TYPE)
                    ->values(['default','env','php','xml'])
                    ->defaultValue('default')
                ->end()
                ->scalarNode(self::KEY_DATABASE_ACCESS_PATH)
                    ->beforeNormalization()
                        ->always(function ($value) {
                            if ($value) {
                                $value = $this->container->get(PathService::class)->checkIfFileOrDirectoryExist($value);
                            }
                            return $value;
                        })
                    ->end()
                ->end()
                ->arrayNode(self::KEY_DATABASE_ACCESS_DATA)
                    ->children()
                        ->scalarNode(self::KEY_DATABASE_ACCESS_DATA_USER)->end()
                        ->scalarNode(self::KEY_DATABASE_ACCESS_DATA_PASSWORD)->end()
                        ->scalarNode(self::KEY_DATABASE_ACCESS_DATA_PORT)->end()
                        ->scalarNode(self::KEY_DATABASE_ACCESS_DATA_HOST)->end()
                    ->end()
                ->end()
            ->end()
        ;
        return $node;
    }

    /**
     * @return ScalarNodeDefinition
     */
    protected function getTmpDir()
    {
        $node = new ScalarNodeDefinition(self::KEY_TMP_DIR);
        $node
            ->beforeNormalization()
                ->always(function ($value) {
                    $path = $this->container->get(PathService::class)->checkIfFileOrDirectoryExist($value, true);
                    if ($path) {
                        $this->container->get(PathService::class)->checkHtaccessFile($path);
                    }
                    return $path;
                })
            ->end()
            ->cannotBeEmpty();
        return $node;
    }

    /**
     * @return ArrayNodeDefinition
     */
    protected function getPresets()
    {
        $node = new ArrayNodeDefinition(self::KEY_PRESETS);
        $node
            ->useAttributeAsKey('name')
            ->scalarPrototype()->cannotBeEmpty()->end();
        return $node;
    }

    /**
     * @return ScalarNodeDefinition
     */
    protected function getDefaultsFile()
    {
        $node = new ScalarNodeDefinition(self::KEY_DEFAULTS_FILE);
        $node
            ->cannotBeEmpty()
            ->defaultValue(null)
            ->beforeNormalization()
                ->always(function ($value) {
                    return $this->container->get(PathService::class)->checkIfFileOrDirectoryExist($value);
                })
            ->end()
            ->validate()
                ->ifTrue(function ($value) {
                    if ($this->container->get(PathService::class)->checkIfFileOrDirectoryExist($value)) {
                        return false;
                    } else {
                        return true;
                    }
                })
                ->thenInvalid('File from "' . self::KEY_DEFAULTS_FILE . '" could not be found.')
            ->end();
        return $node;
    }

    /**
     * @return ScalarNodeDefinition
     */
    protected function getBinaryDbCommand()
    {
        $node = new ScalarNodeDefinition(self::KEY_BINARY_DB_COMMAND);
        $node
            ->cannotBeEmpty()
            ->defaultValue(null)
            ->beforeNormalization()
                ->always(function ($value) {
                    if (empty($value)) {
                        exec('which mysql', $output);
                        if (!empty($output)) {
                            $value = $output[0];
                        }
                    }
                    return $value;
                })
            ->end()
            ->validate()
            ->ifTrue(function ($value) {
                if ($this->container->get(PathService::class)->checkIfCommandExists($value)) {
                    return false;
                } else {
                    return true;
                }
            })
                ->thenInvalid('Command from "' . self::KEY_BINARY_DB_COMMAND . '" could not be found.')
            ->end();

        return $node;
    }

    /**
     * @return ScalarNodeDefinition
     */
    protected function getBinaryDbExport()
    {
        $node = new ScalarNodeDefinition(self::KEY_BINARY_DB_EXPORT);
        $node
            ->defaultValue(null)
            ->cannotBeEmpty()
            ->beforeNormalization()
                ->always(function ($value) {
                    if (empty($value)) {
                        exec('which mysqldump', $output);
                        if (!empty($output)) {
                            $value = $output[0];
                        }
                    }
                    return $value;
                })
            ->end()
            ->validate()
            ->ifTrue(function ($value) {
                if ($this->container->get(PathService::class)->checkIfCommandExists($value)) {
                    return false;
                } else {
                    return true;
                }
            })
                ->thenInvalid('Command from "' . self::KEY_BINARY_DB_EXPORT . '" could not be found.')
            ->end();
        return $node;
    }

    /**
     * @return ScalarNodeDefinition
     */
    protected function getBinaryPacker()
    {
        $node = new ScalarNodeDefinition(self::KEY_BINARY_PACKER);
        $node
            ->defaultValue(null)
            ->cannotBeEmpty()
            ->beforeNormalization()
                ->always(function ($value) {
                    if (empty($value)) {
                        exec('which zip', $output);
                        if (!empty($output)) {
                            $value = $output[0];
                        }
                    }
                    return $value;
                })
            ->end()
            ->validate()
            ->ifTrue(function ($value) {
                if ($this->container->get(PathService::class)->checkIfCommandExists($value)) {
                    return false;
                } else {
                    return true;
                }
            })
                ->thenInvalid('Command from "' . self::KEY_BINARY_PACKER . '" could not be found.')
            ->end();
        return $node;
    }

    /**
     * @return ArrayNodeDefinition
     */
    protected function getApplication()
    {
        $node = new ArrayNodeDefinition(self::KEY_APPLICATION);
        $node
            ->cannotBeEmpty()
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->children()
                    ->arrayNode(self::KEY_TABLES)
                        ->children()
                            ->arrayNode(self::KEY_DETECTION)
                                ->prototype('scalar')->cannotBeEmpty()->end()
                            ->end()
                            ->arrayNode(self::KEY_WHITELIST)
                                ->prototype('scalar')->cannotBeEmpty()->end()
                            ->end()
                            ->arrayNode(self::KEY_WHITELIST_PRESETS)
                                ->prototype('scalar')->cannotBeEmpty()->end()
                            ->end()
                            ->arrayNode(self::KEY_BLACKLIST)
                                ->prototype('scalar')->cannotBeEmpty()->end()
                            ->end()
                            ->arrayNode(self::KEY_BLACKLIST_PRESETS)
                                ->prototype('scalar')->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
        return $node;
    }

    /**
     * @return ArrayNodeDefinition
     */
    protected function getTables()
    {
        $node = new ArrayNodeDefinition(self::KEY_TABLES);
        $node
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->children()
                    ->booleanNode(self::KEY_APPLICATION_BOOLEAN)->defaultTrue()->end()
                    ->arrayNode(self::KEY_WHITELIST)
                        ->prototype('scalar')->cannotBeEmpty()->end()
                    ->end()
                    ->arrayNode(self::KEY_WHITELIST_PRESETS)
                        ->prototype('scalar')->cannotBeEmpty()->end()
                    ->end()
                    ->arrayNode(self::KEY_BLACKLIST)
                        ->prototype('scalar')->cannotBeEmpty()->end()
                    ->end()
                    ->arrayNode(self::KEY_BLACKLIST_PRESETS)
                        ->prototype('scalar')->cannotBeEmpty()->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    /**
     * @return ArrayNodeDefinition
     */
    protected function getDatabases()
    {
        $node = new ArrayNodeDefinition(self::KEY_DATABASES);
        $node
            ->children()
                ->arrayNode(self::KEY_WHITELIST)
                    ->prototype('scalar')->cannotBeEmpty()->end()
                ->end()
                ->arrayNode(self::KEY_WHITELIST_PRESETS)
                    ->prototype('scalar')->cannotBeEmpty()->end()
                ->end()
                ->arrayNode(self::KEY_BLACKLIST)
                    ->prototype('scalar')->cannotBeEmpty()->end()
                ->end()
                ->arrayNode(self::KEY_BLACKLIST_PRESETS)
                    ->prototype('scalar')->cannotBeEmpty()->end()
                ->end()
            ->end();
        return $node;
    }

    /**
     * @return ArrayNodeDefinition
     */
    protected function getCron()
    {
        $node = new ArrayNodeDefinition(self::KEY_CRON);
        $node
            ->isRequired()
            ->children()
                ->scalarNode(self::KEY_CRON_PATTERN)
                    ->cannotBeEmpty()
                    ->isRequired()
                ->end()
                ->integerNode(self::KEY_CRON_HOW_MANY)
                    ->defaultValue(5)
                    ->min(1)
                    ->max(99)
                ->end()
            ->end();
        return $node;
    }

    /**
     * @return ArrayNodeDefinition
     */
    protected function getStorage()
    {
        $node = new ArrayNodeDefinition(self::KEY_STORAGE);
        $node
            ->children()
                ->arrayNode(self::KEY_STORAGE_LOCAL)
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode(self::KEY_STORAGE_LOCAL_PATH)
                                ->beforeNormalization()
                                    ->always(function ($value) {
                                        $path = $this->container->get(PathService::class)->checkIfFileOrDirectoryExist($value, true);
                                        if ($path) {
                                            $this->container->get(PathService::class)->checkHtaccessFile($path);
                                        }
                                        return $path;
                                    })
                                ->end()
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->validate()
                                    ->ifTrue(function ($value) {
                                        if ($value) {
                                            return false;
                                        } else {
                                            return true;
                                        }
                                    })
                                    ->thenInvalid('Directory from "' . self::KEY_STORAGE_LOCAL_PATH . '" could not be found.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
        return $node;
    }
}