<?php

namespace SourceBroker\DatabaseBackup\Service;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Exception;

/**
 * Class PathService
 */
class PathService
{
    /**
     * @param $path
     * @param bool $create
     * @return bool|string
     */
    public function checkIfFileOrDirectoryExist($path, $create = false)
    {
        if (!$this->isAbsolutePath($path)) {
            if ($this->isHomePath($path)) {
                $path = str_replace('~',$this->homeDirectory(), $path);
            } else {
                $path = DB_BACKUP_ROOT_DIR . '/' . $path;
            }
        }

        if (realpath($path) === false && $create) {
            mkdir($path, 0777, true);
        }

        return realpath($path);
    }

    public function checkHtaccessFile($path)
    {
        if (!file_exists($path . '/.htaccess')) {
            file_put_contents($path . '/.htaccess', "Order Allow,Deny \nDeny from all");
        }
    }

    /**
     * Return the user's home directory.
     */
    public function homeDirectory() {
        // Cannot use $_SERVER superglobal since that's empty during UnitUnishTestCase
        // getenv('HOME') isn't set on Windows and generates a Notice.
        $home = getenv('HOME');
        if (!empty($home)) {
            // home should never end with a trailing slash.
            $home = rtrim($home, '/');
        }
        elseif (!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOMEPATH'])) {
            // home on windows
            $home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
            // If HOMEPATH is a root directory the path can end with a slash. Make sure
            // that doesn't happen.
            $home = rtrim($home, '\\/');
        }
        return empty($home) ? NULL : $home;
    }

    /**
     * @param $cmd
     * @return bool
     */
    public function checkIfCommandExists($cmd)
    {
        $return = shell_exec(sprintf("which %s", escapeshellarg($cmd)));
        return !empty($return);
    }

    public function slugify($text, $replacement = '-')
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', $replacement, $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', $replacement, $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    /**
     * @param $path
     * @return bool|string
     */
    protected function getRealPath($path)
    {
        if (!$this->isAbsolutePath($path)) {
            $path = DB_BACKUP_ROOT_DIR . '/' . $path;
        }
        if (empty($realPath = realpath($path))) {
            throw new FileNotFoundException(null, 0, null, $path);
        }

        return $realPath;
    }

    /**
     * @param $path
     * @return bool
     * @throws Exception
     */
    protected function isAbsolutePath($path) {
        if($path === null || $path === '') throw new Exception("Empty path");
        return $path[0] === DIRECTORY_SEPARATOR || preg_match('~\A[A-Z]:(?![^/\\\\])~i',$path) > 0;
    }

    /**
     * @param $path
     * @return bool
     * @throws Exception
     */
    protected function isHomePath($path) {
        if($path === null || $path === '') throw new Exception("Empty path");
        return $path[0] == '~';
    }
}