<?php

/*
 *  Copyright notice
 *
 *  (c) 2012 Daniel Corn <info@cundd.net>, cundd
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 *
 *
 * @package cundd_composer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class Tx_CunddComposer_Utility_ConfigurationUtility
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
    public static function getConfiguration($key){
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
