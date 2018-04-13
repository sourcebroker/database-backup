<?php

namespace SourceBroker\DatabaseBackup\DatabaseAccess;

use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Class PhpProvider
 */
class PhpProvider extends BaseProvider
{

    /**
     * PreProcess
     */
    public function preProcess()
    {
        $data = require_once $this->setting['databaseAccess']['path'];
        $accessor = PropertyAccess::createPropertyAccessor();
        $this->user = $accessor->getValue($data, $this->setting['databaseAccess']['data']['user']);
        $this->password = $accessor->getValue($data, $this->setting['databaseAccess']['data']['password']);

        if (isset($this->setting['databaseAccess']['data']['host'])) {
            $this->host = $accessor->getValue($data, $this->setting['databaseAccess']['data']['host']);
        }
        if (isset($this->setting['databaseAccess']['data']['port'])) {
            $this->port = $accessor->getValue($data, $this->setting['databaseAccess']['data']['port']);
        };
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        return $this->createFile();
    }

    /**
     * {@inheritdoc}
     */
    public function clean()
    {
        $this->removeFile();
        return $this;
    }

}