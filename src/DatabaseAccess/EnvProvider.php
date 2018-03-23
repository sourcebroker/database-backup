<?php

namespace SourceBroker\DatabaseBackup\DatabaseAccess;

use Symfony\Component\Dotenv\Dotenv;

/**
 * Class EnvProvider
 */
class EnvProvider extends BaseProvider {

    /**
     * @var Dotenv
     */
    protected $dotenv;

    /**
     * PreProcess
     */
    public function preProcess()
    {
        $this->dotenv = new Dotenv();
        $this->dotenv->load($this->setting['databaseAccess']['path']);

        $this->user = getenv($this->setting['databaseAccess']['data']['user']);
        $this->password = getenv($this->setting['databaseAccess']['data']['password']);

        if (isset($this->setting['databaseAccess']['data']['port'])) {
            $this->port = getenv($this->setting['databaseAccess']['data']['port']);
        }

        if (isset($this->setting['databaseAccess']['data']['host'])) {
            $this->host = getenv($this->setting['databaseAccess']['data']['host']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clean()
    {
        $this->removeFile();
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        return $this->createFile();
    }
}