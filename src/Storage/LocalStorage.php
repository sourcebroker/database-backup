<?php

namespace SourceBroker\DatabaseBackup\Storage;

/**
 * Class LocalStorage
 */
class LocalStorage extends AbstractStorage
{
    /**
     * {@inheritdoc}
     */
    public function save(array $files)
    {
        foreach ($files as $file) {
            if (file_exists($file)) {
                copy($file, $this->getPath() . '/' . basename($file));
                $parts = array_map(function($v) {
                    if (strpos($v, ':') !== false) {
                        list($key, $value) = explode(':', $v);
                        if ($key == 'key') {
                            $v = implode(':', [$key,'*']);
                        }
                    } else {
                        $v = '*';
                    }
                    return $v;
                }, explode('#', basename($file ,'.sql.zip')));
                $this->removeOutdatedDumps(
                    $this->getPath() . '/' . implode('#', $parts) . '.sql.zip',
                    $this->globalConfig['cron']['howMany']
                );
            }
        }
    }

    /**
     * @return string
     */
    protected function getPath()
    {
        $path = $this->settings['path'] . '/' . $this->key;
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }

    /**
     * @param string $pattern
     * @param integer $howMany
     */
    protected function removeOutdatedDumps($pattern, $howMany)
    {
        $fileNames = glob($pattern);
        rsort($fileNames);
        foreach ($fileNames as $key => $fileName) {
            if ($key >= $howMany) {
                unlink($fileName);
            }
        }
    }
}