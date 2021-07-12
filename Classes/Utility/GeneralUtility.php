<?php
declare(strict_types=1);

namespace Cundd\CunddComposer\Utility;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use UnexpectedValueException;
use function file_exists;
use function is_array;
use function is_dir;
use function is_integer;
use function mkdir;
use function octdec;
use function realpath;
use function rmdir;
use function unlink;

class GeneralUtility
{
    /**
     * Dumps a given variable (or the given variables) wrapped into a 'pre' tag.
     *
     * @param mixed ...$var1
     */
    public static function pd(...$var1)
    {
        // noop
    }

    /**
     * Merge two arrays recursively
     *
     * Unlike the implementation of array_merge_recursive() the second value will
     * overwrite the first, if a key is already set.
     *
     * Thanks to Gabriel Sobrinho http://www.php.net/manual/en/function.array-merge-recursive.php#92195
     *
     * @param array   $array1
     * @param array   $array2
     * @param boolean $strict If set to TRUE an exception will be thrown if a key already is set with a different value
     * @return array Returns the merged array
     * @throws UnexpectedValueException if the strict mode is enabled and a key already exists
     */
    public static function arrayMergeRecursive(array $array1, array $array2, bool $strict = false): array
    {
        $merged = $array1;
        foreach ($array2 as $key => &$value) {
            if ($strict && isset($merged[$key]) && !is_array($merged[$key]) && $merged[$key] != $value) {
                throw new UnexpectedValueException(
                    'Key "' . $key . '" already exists with a different value',
                    1360672930
                );
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
     * Return the path to the extensions base
     *
     * @return string
     */
    public static function getExtensionPath(): string
    {
        $path = __DIR__ . '/../../';

        return (realpath($path) ?: $path) . '/';
    }

    /**
     * Return the path to the resources folder
     *
     * @return string
     */
    public static function getPathToResource(): string
    {
        return self::getExtensionPath() . 'Resources/';
    }

    /**
     * Return the path to the resources folder
     *
     * @return string
     */
    public static function getPathToVendorDirectory(): string
    {
        return self::getExtensionPath() . 'vendor/';
    }

    /**
     * Return the path to the temporary directory
     *
     * @return string
     */
    public static function getTempPath(): string
    {
        return self::getPathToResource() . 'Private/Temp/';
    }

    /**
     * Return the path to the composer phar
     *
     * @return string
     */
    public static function getComposerPath(): string
    {
        return self::getPathToResource() . '/Private/PHP/composer.phar';
    }

    /**
     * Create the given directory if it does not already exist
     *
     * @param string $directory
     * @return boolean Returns TRUE if the directory exists, or could be created, otherwise FALSE
     */
    public static function createDirectoryIfNotExists(string $directory): bool
    {
        // Check if the directory exists
        if (!file_exists($directory)) {
            $permission = 0777;
            if (isset($GLOBALS['TYPO3_CONF_VARS'])
                && isset($GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask'])
                && $GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask']
            ) {
                $permission = octdec($GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask']);
            }

            return @mkdir($directory, $permission, true);
        }

        return true;
    }

    /**
     * Make sure that the temporary directory exists
     *
     * @return void
     * @throws RuntimeException if the temporary directory does not exist
     */
    public static function makeSureTempPathExists()
    {
        $workingDir = self::getTempPath();

        // Check if the working/temporary directory exists
        if (!self::createDirectoryIfNotExists($workingDir)) {
            throw new RuntimeException(
                'Working directory "' . $workingDir . '" doesn\'t exists and can not be created',
                1359541465
            );
        }
    }

    /**
     * Remove all files in the given directory
     *
     * @param string $directory
     * @return boolean TRUE on success, otherwise FALSE
     */
    public static function removeDirectoryRecursive(string $directory):bool
    {
        $success = true;
        if (!file_exists($directory)) {
            return false;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $path) {
            /** @var SplFileInfo $path */
            $fileName = $path->getFilename();
            if ($fileName === '.' || $fileName === '..') {
                continue;
            }
            if ($path->isLink()) {
                $success *= unlink($path->getPathname());
            } else {
                if ($path->isDir()) {
                    $success *= rmdir($path->getPathname());
                } else {
                    $success *= unlink($path->getPathname());
                }
            }
        }
        if (is_dir($directory)) {
            rmdir($directory);
        }

        return $success;
    }
}
