<?php

namespace SourceBroker\DatabaseBackup\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use SourceBroker\DatabaseBackup\Configuration\CommandConfiguration;

/**
 * Class DatabaseBackupService
 */
class DatabaseBackupService {

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     * @return $this
     */
    public function setKey(string $key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Execute database dump
     *
     * @return array
     * @throws \Exception
     */
    public function execute()
    {
        if (empty($this->key) || empty($this->config)) {
            throw new \Exception('Missing configuration for MysqlBackupService');
        }

        $dateForFilename = date('Ymd_His');
        $results = [];

        $databases = $this->runMysqlCommand('show databases');
        $whitelistDatabases = $this->filterWithRegexp(
            $this->config[CommandConfiguration::KEY_DATABASES][CommandConfiguration::KEY_WHITELIST],
            (array)$databases
        );

        $blacklistDatabases = $this->filterWithRegexp(
            $this->config[CommandConfiguration::KEY_DATABASES][CommandConfiguration::KEY_BLACKLIST],
            (array)$databases
        );

        foreach (array_diff($whitelistDatabases, $blacklistDatabases) as $database) {
            $tables = $this->runMysqlCommand('use ' . $database . '; show tables;');
            $applicationDetected = $this->detectApplicationBasedOnTables(
                $tables,
                $this->config[CommandConfiguration::KEY_APPLICATION]
            );

            $blacklistTables = [];
            $whitelistTables = $tables;
            if (isset($this->config[CommandConfiguration::KEY_TABLES][$database])) {
                $whitelistPattern = $this->config[CommandConfiguration::KEY_TABLES][$database][CommandConfiguration::KEY_WHITELIST];
                $blacklistPattern = $this->config[CommandConfiguration::KEY_TABLES][$database][CommandConfiguration::KEY_BLACKLIST];
                if ($applicationDetected !== null && $this->config[CommandConfiguration::KEY_TABLES][$database][CommandConfiguration::KEY_APPLICATION_BOOLEAN]) {
                    $whitelistPattern = array_merge(
                            $whitelistPattern,
                            $this->getWhitelistPatternFromApplication($applicationDetected)
                        );

                    $blacklistPattern = array_merge(
                        $blacklistPattern,
                        $this->getBlacklistPatternFromApplication($applicationDetected)
                    );
                }
                $whitelistTables = $this->filterWithRegexp(
                    $whitelistPattern,
                    (array)$tables
                );
                $blacklistTables = $this->filterWithRegexp(
                    $blacklistPattern,
                    (array)$tables
                );
            } else {
                if ($applicationDetected !== null) {
                    $whitelistTables = $this->getWhitelistTablesFromApplication($applicationDetected, $tables);
                    $blacklistTables = $this->getBlacklistTablesFromApplication($applicationDetected, $tables);
                }
            }

            $ignoredTables = array_merge($blacklistTables, array_diff($tables, $whitelistTables));

            if (is_array($ignoredTables)) {
                $ignoredTables = '--ignore-table=' . $database . '.' .
                    implode(' --ignore-table=' . $database . '.', $ignoredTables);
            }

            $tempFile = $this->config[CommandConfiguration::KEY_TMP_DIR] . '/' . implode('#', [
                    'database:' . $database, 'key:' . $this->key, $dateForFilename
                ]);

            $command =
                $this->config[CommandConfiguration::KEY_BINARY_DB_EXPORT] . ' --defaults-file='
                . $this->config['defaultsFile'] . ' --quick --quote-names ' . $ignoredTables . ' '
                . escapeshellarg($database) . ' -r ' . escapeshellarg($tempFile . '.sql');
            exec($command);

            $command =
                $this->config[CommandConfiguration::KEY_BINARY_PACKER] . ' -j ' . $tempFile . '.sql.zip' . ' '
                . $tempFile . '.sql';
            exec($command);

            $command = 'rm ' . $tempFile . '.sql';
            exec($command);

            $results[] = $tempFile . '.sql.zip';
        }

        return $results;
    }

    /**
     * @param $application
     * @param $tables
     * @return array
     */
    protected function getWhitelistTablesFromApplication($application, $tables)
    {
        return
            $this->filterWithRegexp(
                $this->getWhitelistPatternFromApplication($application),
                (array)$tables
            );
    }

    /**
     * @param $application
     * @param $tables
     * @return array
     */
    protected function getBlacklistTablesFromApplication($application, $tables)
    {
        return
            $this->filterWithRegexp(
                $this->getBlacklistPatternFromApplication($application),
                (array)$tables
            );
    }

    /**
     * @param $application
     * @return mixed
     */
    protected function getWhitelistPatternFromApplication($application)
    {
        return
            $this->config
            [CommandConfiguration::KEY_APPLICATION]
            [$application]
            [CommandConfiguration::KEY_TABLES]
            [CommandConfiguration::KEY_WHITELIST]
            ;
    }

    /**
     * @param $application
     * @return mixed
     */
    protected function getBlacklistPatternFromApplication($application)
    {
        return
            $this->config
                [CommandConfiguration::KEY_APPLICATION]
                [$application]
                [CommandConfiguration::KEY_TABLES]
                [CommandConfiguration::KEY_BLACKLIST]
            ;
    }


    /**
     * @param $command string Command to execute
     * @return array Array of output lines
     * @throws \Exception
     */
    protected function runMysqlCommand($command)
    {
        exec($this->config[CommandConfiguration::KEY_BINARY_DB_COMMAND] . ' --defaults-file=' .
            escapeshellarg($this->config[CommandConfiguration::KEY_DEFAULTS_FILE]) . ' -B -s -e "' . $command . '" ', $output,
            $status);
        if ($status !== 0) {
            throw new \Exception('The execution of mysql command: "' . $command . '" failed with following output:' . print_r($output,
                    true));
        }
        return $output;
    }

    /**
     * Filter $haystack array items with items from array $patterns.
     * Example usage:
     * filterWithRegexp(['cf_*', 'bcd'], ['abc', 'cf_test1', 'bcd' ,'cf_test2', 'cde']) will return ['cf_test1', 'bcd', 'cf_test2']
     *
     * @param $patterns
     * @param array $haystack
     * @return array
     */
    protected function filterWithRegexp(array $patterns, array $haystack)
    {
        $foundItems = [];
        foreach ((array)$patterns as $pattern) {
            $regexp = false;

            set_error_handler(function () {
            }, E_WARNING);
            $isValidPattern = preg_match($pattern, '') !== false;
            $isValidPatternDelimiters = preg_match('/^' . $pattern . '$/', '') !== false;
            restore_error_handler();

            if (preg_match('/^[\/\#\+\%\~]/', $pattern) && $isValidPattern) {
                $regexp = $pattern;
            } elseif ($isValidPatternDelimiters) {
                $regexp = '/^' . $pattern . '$/i';
            }
            if ($regexp) {
                $foundItems = array_merge($foundItems, preg_grep($regexp, $haystack));
            } elseif (in_array($pattern, $haystack)) {
                $foundItems[] = $pattern;
            }
        }
        return $foundItems;
    }

    /**
     * @param $tables
     * @param $applications
     * @return null
     */
    protected function detectApplicationBasedOnTables($tables, $applications)
    {
        $applicationDetected = null;
        foreach ($applications as $application => $data) {
            if (array_intersect($data[CommandConfiguration::KEY_TABLES][CommandConfiguration::KEY_DETECTION], $tables)) {
                $applicationDetected = $application;
                break;
            }
        }
        return $applicationDetected;
    }


}