<?php

namespace SourceBroker\DatabaseBackup\DatabaseAccess;

/**
 * Class BaseProvider
 */
abstract class BaseProvider implements ProviderInterface
{

    /**
     * @var string
     */
    protected $user;

    /**
     * @var string;
     */
    protected $password;

    /**
     * @var string
     */
    protected $host = 'localhost';

    /**
     * @var int
     */
    protected $port = 3306;

    /**
     * @var string
     */
    protected $dir;

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var array
     */
    protected $setting;

    public function __construct($data, $key)
    {
        $this->setting = $data;
        $this->key = $key;
        $this->dir = $data['tmpDir'];
        $this->fileName = uniqid(rand(), true) . $key . '.cnf';
        $this->preProcess();
    }

    /**
     * PreProcess function
     */
    protected function preProcess()
    {
    }

    /**
     * Create file with database access
     */
    protected function createFile()
    {
        if (file_exists($this->dir)) {
            $mysql = "[mysql]\n";
            $mysqlDump = "[mysqldump]\n";
            $data = ""
                . "user = '{$this->user}'\n"
                . "password = '{$this->password}'\n"
                . "host = '{$this->host}'\n"
                . "port = '{$this->port}'\n"
                . "\n";
            file_put_contents($this->dir . '/' . $this->fileName, $mysql . $data . $mysqlDump . $data);
            return $this->dir . '/' . $this->fileName;
        } else {
            return null;
        }
    }

    /**
     * Remove file with database access
     */
    protected function removeFile()
    {
        if (file_exists($this->dir . '/' . $this->fileName)) {
            unlink($this->dir . '/' . $this->fileName);
        }
    }
}