<?php

namespace SourceBroker\DatabaseBackup\DatabaseAccess;

/**
 * Class XmlProvider
 */
class XmlProvider extends BaseProvider {

    public function process(){
        throw new \Exception('XML provider haven\'t implemented yet');
    }
    public function clean(){}
}