<?php
namespace Cundd\CunddComposer\Installer;

/***************************************************************
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
 ***************************************************************/

use Cundd\CunddComposer\Utility\GeneralUtility as ComposerGeneralUtility;

/**
 *
 *
 * @package cundd_composer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class AssetInstaller
{
    /**
     * An array of paths to look for assets inside the installed packages
     *
     * @var array
     */
    protected $assetPaths = array();

    /**
     * Definition writer
     *
     * @var \Cundd\CunddComposer\Definition\Writer
     * @inject
     */
    protected $definitionWriter;

    /**
     * Creates symlinks for installed assets
     *
     * Loops through all the installed packages and checks if they contain the
     * Resources/Public/ directory. If it exists a symlink to the directory will
     * be created inside of CunddComposer's Resources/Public folder.
     *
     * Example:
     *  Package foo/bar contains a directory Resources/Public
     *  CunddComposer will create a symlink at
     *  EXT:cundd_composer/Resources/Public/foo_bar/ which will point to
     *  EXT:cundd_composer/vendor/foo/bar/Resources/Public/
     *
     * @return array<array<string>> Returns an array of installed assets
     */
    public function installAssets()
    {
        $installedAssets = array();
        $mergedComposerJson = $this->definitionWriter->getMergedComposerJson(true);

        // Remove the old links
        $assetsDirectoryPath = $this->getAssetsDirectoryPath();
        ComposerGeneralUtility::removeDirectoryRecursive($assetsDirectoryPath);
        ComposerGeneralUtility::createDirectoryIfNotExists($assetsDirectoryPath);
        if (!file_exists($assetsDirectoryPath)) {
            throw new \RuntimeException(
                sprintf('Directory "%s" does not exists and can not be created', $assetsDirectoryPath),
                1362514209
            );
        }

        // Merge the require and require-dev packages
        $requiredPackages = $mergedComposerJson['require'];
        if (!is_array($requiredPackages)) {
            $requiredPackages = array();
        }
        if (is_array($mergedComposerJson['require-dev'])) {
            $requiredPackages = array_merge($requiredPackages, $mergedComposerJson['require-dev']);
        }
        $installedAssets = array_merge($installedAssets, $this->installAssetsOfPackages($requiredPackages));
        return $installedAssets;
    }

    /**
     * Loops through the packages and the available Asset paths and installs
     * found packages
     *
     * @param  array $requiredPackages Array of packages
     * @return array<array<string>> Returns an array of installed assets
     */
    public function installAssetsOfPackages($requiredPackages)
    {
        ComposerGeneralUtility::pd($requiredPackages);

        $installedAssets = array();
        $vendorDirectory = ComposerGeneralUtility::getPathToVendorDirectory();
        $assetsDirectoryPath = $this->getAssetsDirectoryPath();

        foreach ($requiredPackages as $package => $version) {
            $packagePath = $vendorDirectory . $package . DIRECTORY_SEPARATOR;
            $symlinkDirectory = $assetsDirectoryPath . str_replace(DIRECTORY_SEPARATOR, '_', $package);

            $allAssetPaths = $this->getAssetPaths();

            /*
             * Add the package name as file to the Asset paths
             *
             * Convert "cundd/jquery-backstretch" to "jquery.backstretch"
             */
            $mainJavaScriptFile = str_replace('-', '.', substr($package, strrpos($package, '/') + 1));
            $allAssetPaths[] = $mainJavaScriptFile . '.js';
            $allAssetPaths[] = $mainJavaScriptFile . '.min.js';
            foreach ($allAssetPaths as $currentAssetPath) {
                $packagePublicResourcePath = $packagePath . $currentAssetPath;

                ComposerGeneralUtility::pd('Checking if "' . $packagePublicResourcePath . '" exists: ' . (file_exists($packagePublicResourcePath) ? 'Yes' : 'No'));

                // Check if the public resource folders exist
                if (file_exists($packagePublicResourcePath)) {
                    ComposerGeneralUtility::createDirectoryIfNotExists($symlinkDirectory);

                    $symlinkName = $symlinkDirectory . DIRECTORY_SEPARATOR . $currentAssetPath;

                    // Add the asset information to the array
                    $installedAssets[$package . $currentAssetPath] = array(
                        'name'           => $package,
                        'version'        => $version,
                        'assetKey'       => $currentAssetPath,
                        'source'         => $this->getRelativePathOfUri($packagePublicResourcePath),
                        'sourceDownload' => $this->getDownloadUriOfUri($packagePublicResourcePath),
                        'target'         => $this->getRelativePathOfUri($symlinkName),
                        'targetDownload' => $this->getDownloadUriOfUri($symlinkName)
                    );
                    // Create the symlink if it doesn't exist
                    if (!file_exists($symlinkName)) {
                        /**
                         * Path to the target relative to the directory inside
                         * the Assets folder
                         *
                         * @var string
                         */
                        $symlinkSource = './../../../../' . $this->getRelativePathOfUri($packagePublicResourcePath);
                        $symlinkCreated = symlink($symlinkSource, $symlinkName);
                        if ($symlinkCreated) {
                            ComposerGeneralUtility::pd('Created symlink of "' . $packagePublicResourcePath . '" to "' . $symlinkName . '"');
                        } else {
                            ComposerGeneralUtility::pd('Could not create symlink of "' . $packagePublicResourcePath . '" to "' . $symlinkName . '"');
                        }
                    }
                }
            }
        }
        return $installedAssets;
    }

    /**
     * Returns the relative path of the given URI
     *
     * @param string $uri
     * @return string
     */
    public function getRelativePathOfUri($uri)
    {
        return str_replace(ComposerGeneralUtility::getExtensionPath(), '', $uri);
    }

    /**
     * Returns the URI to download or reference the given URI
     *
     * @param  string $uri
     * @return string
     */
    public function getDownloadUriOfUri($uri)
    {
        static $baseUrl = null;
        if ($baseUrl === null) {
            $baseUrl = '';
            if (isset($GLOBALS['TSFE']) && isset($GLOBALS['TSFE']->baseUrl)) {
                $baseUrl = $GLOBALS['TSFE']->baseUrl;
            }

            if (TYPO3_MODE === 'BE') {
                $baseUrl .= '../';
            }
            $baseUrl .= 'typo3conf/ext/cundd_composer/';
        }
        return $baseUrl . $this->getRelativePathOfUri($uri);
    }

    /**
     * Returns the array of relative paths that will be used to find assets
     *
     * The installer will loop through this paths. If the current path exists
     * inside the package a symlink to this directory will be created and no
     * further asset paths will be checked.
     *
     * Example:
     *    Package: foo/bar
     *    Current asset path: Resources/Public/
     *    Check if exists: EXT:cundd_composer/vendor/foo/bar/Resources/Public/
     *  If not move on
     *  Current asset path: build
     *    Check if exists: EXT:cundd_composer/vendor/foo/bar/build
     *  If exists create symlink to EXT:cundd_composer/vendor/foo/bar/build and break
     *
     * @return array<string>
     */
    public function getAssetPaths()
    {
        return $this->assetPaths;
    }

    /**
     * Sets the asset paths
     *
     * @param array|string $assetPaths
     */
    public function setAssetPaths($assetPaths){
        $this->assetPaths = array();
            if ($assetPaths) {
                $assetPaths = explode(',', $assetPaths);
                $this->assetPaths = array_map(function ($path) {
                    $path = trim($path);
                    return $path;
                }, $assetPaths);
                ComposerGeneralUtility::pd($this->assetPaths);
            }

    }

    /**
     * Returns the path to the assets
     *
     * @return string
     */
    public function getAssetsDirectoryPath()
    {
        return ComposerGeneralUtility::getPathToResource() . 'Public/Assets/';
    }
}
