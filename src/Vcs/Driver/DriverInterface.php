<?php
namespace SourceBroker\DatabaseBackup\Vcs\Driver;

/**
 * @package SourceBroker\DatabaseBackup\Vcs\Driver
 */
interface DriverInterface
{
    /**
     * Creates a Tag and push the specific tag into the remote.
     *
     * @param string $tag
     * @param string $branch
     * @param string $path
     * @return void
     */
    public function tag($tag, $branch, $path);

    /**
     * Returns the latest tag from the given repository.
     * If no tag can be evaluated it will return "0.0.0".
     *
     * @return string
     */
    public function getLatestTag();

    /**
     * Commits given files into the remote repository.
     *
     * @param array $files
     * @param string $path
     * @param string $message
     * @return void
     */
    public function commit(array $files, $path, $message = '');
}
