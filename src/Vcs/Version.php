<?php

namespace SourceBroker\DatabaseBackup\Vcs;

/**
 * @package SourceBroker\DatabaseBackup\Vcs
 */
class Version
{
    /**
     * @var string
     */
    const INCREASE_PATCH = 'patch';

    /**
     * @var string
     */
    const INCREASE_MINOR = 'minor';

    /**
     * @var string
     */
    const INCREASE_MAJOR = 'major';

    /**
     * @param string $version
     * @param string $type
     * @return string
     */
    public function increase($version, $type = self::INCREASE_PATCH)
    {
        $version = new \vierbergenlars\SemVer\version($version);

        return $version->inc($type)->getVersion();
    }
}
