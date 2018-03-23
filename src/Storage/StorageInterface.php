<?php

namespace SourceBroker\DatabaseBackup\Storage;

/**
 * Interface StorageInterface
 */
interface StorageInterface
{
    /**
     * @param array $settings
     * @return $this
     */
    public function setSettings($settings);

    /**
     * @return array
     */
    public function getSettings();

    /**
     * @param array $config
     * @return $this
     */
    public function setGlobalConfig($config);

    /**
     * @return array
     */
    public function getGlobalConfig();

    /**
     * @param string $key
     * @return $this
     */
    public function setKey($key);

    /**
     * @return string
     */
    public function getKey();

    /**
     * @param array $files
     * @return $this
     */
    public function save(array $files);
}