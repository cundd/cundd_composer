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
class Tx_CunddComposer_GeneralUtility
{
    /**
     * Dumps a given variable (or the given variables) wrapped into a 'pre' tag.
     *
     * @param    mixed $var1
     * @return    string The printed content
     */
    public static function pd($var1 = '__iresults_pd_noValue')
    {
        if (class_exists('Tx_Iresults')) {
            $arguments = func_get_args();
            call_user_func_array(array('Tx_Iresults', 'pd'), $arguments);
        }
    }

    /**
     * Merge two arrays recursively.
     *
     * Unlike the implementation of array_merge_recursive() the second value will
     * overwrite the first, if a key is already set.
     *
     * Thanks to Gabriel Sobrinho http://www.php.net/manual/en/function.array-merge-recursive.php#92195
     *
     * @param array   $array1
     * @param array   $array2
     * @param boolean $strict If set to TRUE an exception will be thrown if a key already is set with a different value
     * @throws UnexpectedValueException if the strict mode is enabled and a key already exists
     * @return  array Returns the merged array
     */
    public static function arrayMergeRecursive($array1, $array2, $strict = false)
    {
        $merged = $array1;
        foreach ($array2 as $key => &$value) {
            if ($strict && isset($merged[$key]) && !is_array($merged[$key]) && $merged[$key] != $value) {
                throw new UnexpectedValueException('Key "' . $key . '" already exists with a different value',
                    1360672930);
            }
            if (is_array($value) // If the current value is an array it may has to be merged
                && !is_integer($key) // Check if we are not inside of an array (only merge objects)
                && isset($merged[$key])
                && is_array($merged[$key])
            ) {
                $value = self::arrayMergeRecursive($merged[$key], $value);
            }

            if (is_integer($key)) {
                $merged[] = $value;
            } else {
                $merged[$key] = $value;
            }
        }
        return $merged;
    }

    /**
     * Returns the path to the extensions base
     *
     * @return string
     */
    public static function getExtensionPath()
    {
        return __DIR__ . '/../';
    }

    /**
     * Returns the path to the resources folder
     *
     * @return string
     */
    public static function getPathToResource()
    {
        return self::getExtensionPath() . 'Resources/';
    }

    /**
     * Returns the path to the temporary directory
     *
     * @return string
     */
    public static function getTempPath()
    {
        return self::getPathToResource() . 'Private/Temp/';
    }
}
