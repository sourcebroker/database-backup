<?php

namespace SourceBroker\DatabaseBackup\Configuration;

class ConfigurationMerger
{

    /**
     * @param array $config
     * @return array
     */
    public function process($config)
    {
        $config[CommandConfiguration::KEY_DEFAULTS] = $this->mergeDefaultForTables($config[CommandConfiguration::KEY_DEFAULTS]);

        foreach ($config[CommandConfiguration::KEY_CONFIGS] as $taskName => &$taskConfig) {
            $taskConfig = $this->mergeDefaultForTables($taskConfig);

            foreach ($config[CommandConfiguration::KEY_DEFAULTS] as $key => $default) {
                if (empty($taskConfig[$key])) {
                    $taskConfig[$key] = $default;
                }
            }
        }
        return $config;
    }

    /**
     * Merge tables _default_ to each database entry tables description
     *
     * @param array $config
     * @return array
     */
    protected function mergeDefaultForTables($config)
    {
        if (isset($config[CommandConfiguration::KEY_TABLES][CommandConfiguration::KEY_TABLES_DEFAULT]))
        {
            foreach ($config[CommandConfiguration::KEY_TABLES] as $dbName => &$dbTables) {
                if ($dbName == CommandConfiguration::KEY_TABLES_DEFAULT) {
                    continue;
                }

                if (!isset($dbTables[CommandConfiguration::KEY_WHITELIST])) {
                    $dbTables[CommandConfiguration::KEY_WHITELIST] =
                        $config[CommandConfiguration::KEY_TABLES][CommandConfiguration::KEY_TABLES_DEFAULT][CommandConfiguration::KEY_WHITELIST];
                }
                if (!isset($dbTables[CommandConfiguration::KEY_BLACKLIST])) {
                    $dbTables[CommandConfiguration::KEY_BLACKLIST] =
                        $config[CommandConfiguration::KEY_TABLES][CommandConfiguration::KEY_TABLES_DEFAULT][CommandConfiguration::KEY_BLACKLIST];
                }
            }
        }

        return $config;
    }
}
