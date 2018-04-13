<?php

namespace SourceBroker\DatabaseBackup\Storage;

/**
 * Class AbstractStorage
 */
abstract class AbstractStorage implements StorageInterface
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var array
     */
    protected $globalConfig;

    /**
     * {@inheritdoc}
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * {@inheritdoc}
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getGlobalConfig()
    {
        return $this->globalConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function setGlobalConfig($config)
    {
        $this->globalConfig = $config;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }


}