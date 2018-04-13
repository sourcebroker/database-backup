<?php

namespace SourceBroker\DatabaseBackup\Vcs\Driver;

use Webcreate\Vcs\Common\Adapter\CliAdapter;
use Webcreate\Vcs\Common\Reference;
use Webcreate\Vcs\Git;

/**
 * Wrapper Class for Webcreate\Vcs\Git
 *
 * @package SourceBroker\DatabaseBackup\Vcs\Driver
 */
class GitDriver implements DriverInterface
{
    /**
     * @var Git
     */
    private $git;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $executable;

    /**
     * @param string $url
     * @param string $executable
     */
    public function __construct($url, $executable = 'git')
    {
        $this->url = $url;
        $this->executable = $executable;
    }

    /**
     * @param string $tag
     * @param string $branch
     * @param string $path
     * @throws \Exception
     * @return void
     */
    public function tag($tag, $branch, $path)
    {
        try {
            $this->getGit()->getAdapter()->execute('tag', [$tag], $path);
            $this->getGit()->getAdapter()->execute('checkout', [$branch], $path);
            $this->getGit()->getAdapter()->execute('branch', ['--delete', '--force', 'dist'], $path);
            $this->getGit()->getAdapter()->execute('push', ['--tags'], $path);
        } catch (\Exception $e) {
            $this->getGit()->getAdapter()->execute('reset', ['--hard'], $path);
            $this->getGit()->getAdapter()->execute('tag', ['-d', $tag], $path);
            throw $e;
        }
    }

    /**
     * @return Git
     */
    protected function getGit()
    {
        if (null === $this->git) {
            $this->git = new Git($this->url);
            /** @var CliAdapter $adapter */
            $adapter = $this->git->getAdapter();
            $adapter->setExecutable($this->executable);
        }

        return $this->git;
    }

    /**
     * @param string $path
     * @throws \Exception
     */
    public function checkoutDistBranch($path)
    {
        try {
            $this->getGit()->getAdapter()->execute('checkout', ['-b', 'dist'], $path);
        } catch (\Exception $e) {
            if (preg_match("/branch .+ already exists/", $e->getMessage()) === 1) {
                $this->getGit()->getAdapter()->execute('checkout', ['dist'], $path);
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param array $files
     * @param string $path
     * @param string $message
     * @throws \Exception
     */
    public function commit(array $files, $path, $message = '')
    {
        try {
            $this->getGit()->getAdapter()->execute('add', $files, $path);
            $this->getGit()->getAdapter()->execute('commit', array_merge(['-m', $message], $files), $path);
        } catch (\Exception $e) {
            if (false !== strpos($e->getMessage(), 'nothing to commit')) {
                return;
            }
            if (false !== strpos($e->getMessage(), 'nothing added to commit')) {
                return;
            }
            throw $e;
        }
    }

    /**
     * @param array $data
     * @param $path
     * @throws \Exception
     */
    public function config(array $data, $path)
    {
        try {
            foreach ($data as $key => $value) {
                $this->getGit()->getAdapter()->execute('config', [$key, $value], $path);
            }
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * @return string
     */
    public function getLatestTag()
    {
        $tags = [];
        foreach ($this->getGit()->tags() as $reference) {
            /** @var Reference $reference */
            $tags[] = $reference->getName();
        }

        usort($tags, 'version_compare');

        if (empty($tags)) {
            return '0.0.0';
        }

        return array_pop($tags);
    }
}
