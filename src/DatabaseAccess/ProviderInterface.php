<?php

namespace SourceBroker\DatabaseBackup\DatabaseAccess;

interface ProviderInterface
{

    /**
     * @return string
     */
    public function process();

    /**
     * @return $this
     */
    public function clean();
}