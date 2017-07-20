<?php

namespace Cundd\CunddComposer\Utility;

class ConfigurationUtility
{

    /**
     * Configuration loaded from extConf
     *
     * @var array
     */
    protected static $configuration = null;

    /**
     * Returns the extension configuration for the given key
     *
     * @param  string $key Configuration key
     * @return mixed      Configuration value
     */
    public static function getConfiguration($key)
    {
        // Read the configuration from the globals
        if (self::$configuration === null) {
            if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['cundd_composer'])) {
                self::$configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['cundd_composer']);
            }
        }

        // Return the configuration value
        if (self::$configuration && isset(self::$configuration[$key])) {
            return self::$configuration[$key];
        }

        return false;
    }

    /**
     * Returns the path to the PHP executable
     *
     * @return string
     */
    public static function getPHPExecutable()
    {
        $phpExecutable = static::getConfiguration('phpExecutable');
        if (!$phpExecutable) {
            $phpExecutable = static::getPHPExecutableFromPath();
        }

        return trim($phpExecutable);
    }


    /**
     * Tries to find the PHP executable.
     *
     * @return string Returns the path to the PHP executable, or FALSE on error
     */
    protected static function getPHPExecutableFromPath()
    {
        if (defined('PHP_BINDIR') && @file_exists(PHP_BINDIR . '/php') && is_executable(PHP_BINDIR . '/php')) {
            return PHP_BINDIR . '/php';
        }
        $paths = explode(PATH_SEPARATOR, getenv('PATH'));
        foreach ($paths as $path) {
            // we need this for XAMPP (Windows)
            if (strstr($path, 'php.exe') && isset($_SERVER['WINDIR']) && @file_exists($path) && is_file($path)) {
                return $path;
            } else {
                $php_executable = $path . DIRECTORY_SEPARATOR . 'php' . (isset($_SERVER['WINDIR']) ? '.exe' : '');
                if (@file_exists($php_executable) && is_file($php_executable)) {
                    return $php_executable;
                }
            }
        }

        return false; // not found
    }
}
