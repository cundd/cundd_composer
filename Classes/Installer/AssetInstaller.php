<?php

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

ini_set('display_errors', TRUE);

/**
 *
 *
 * @package cundd_composer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class Tx_CunddComposer_Installer_AssetInstaller {
	/**
	 * The package controller
	 * @var Tx_CunddComposer_Controller_PackageController
	 */
	protected $controller = NULL;

	/**
	 * An array of paths to look for assets inside the installed packages
	 * @var array
	 */
	protected $assetPaths = array();

	/**
	 * Inject the Package Controller
	 *
	 * @param Tx_CunddComposer_Controller_PackageController $controller
	 * @return void
	 */
	public function manuallyInjectController(Tx_CunddComposer_Controller_PackageController $controller) {
		$this->controller = $controller;
	}

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
	 * @return array<array<string>>	Returns an array of installed assets
	 */
	public function installAssets() {
		if (!$this->controller->getConfiguration('allowInstallAssets')) {
			return array();
		}
		$success = TRUE;
		$installedAssets = array();
		$mergedComposerJson = $this->controller->getMergedComposerJson(TRUE);


		// Remove the old links
		$assetsDirectoryPath = $this->getAssetsDirectoryPath();
		$this->removeDirectoryRecursive($assetsDirectoryPath);
		$this->createDirectoryIfNotExists($assetsDirectoryPath);
		if (!file_exists($assetsDirectoryPath)) {
			throw new \RuntimeException('Directory "' . $assetsDirectoryPath . '" doesn\'t exists and can not be created', 1362514209);
		}
		#echo $this->getAssetsDirectoryPath(). __LINE__ . ':' . t3lib_div::rmdir($this->getAssetsDirectoryPath(), TRUE);
		#echo __LINE__ . ':' . t3lib_div::mkdir($this->getAssetsDirectoryPath());

		// foreach ($mergedComposerJson as $packageName => $composerJson) {


		#extra


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
	 * @return @return array<array<string>>	Returns an array of installed assets
	 */
	public function installAssetsOfPackages($requiredPackages) {
		$installedAssets = array();
		$vendorDirectory = $this->getExtensionPath() . 'vendor/';
		$assetsDirectoryPath = $this->getAssetsDirectoryPath();

		foreach ($requiredPackages as $package => $version) {
			$packagePublicResourcePath = 'Not found';
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

				$this->controller->pd('Checking if "' . $packagePublicResourcePath . '" exists: ' . (file_exists($packagePublicResourcePath) ? 'Yes' : 'No'));

				// Check if the public resource folders exist
				if (file_exists($packagePublicResourcePath)) {
					$this->createDirectoryIfNotExists($symlinkDirectory);

					$symlinkName = $symlinkDirectory . DIRECTORY_SEPARATOR . $currentAssetPath;

					// Add the asset information to the array
					$installedAssets[$package . $currentAssetPath] = array(
						'name' 		=> $package,
						'version' 	=> $version,
						'assetKey' 	=> $currentAssetPath,
						'source' 	=> $this->getRelativePathOfUri($packagePublicResourcePath),
						'target' 	=> $this->getRelativePathOfUri($symlinkName)
					);

					// Create the symlink if it doesn't exist
					if (!file_exists($symlinkName)) {
						$symlinkCreated = symlink($packagePublicResourcePath, $symlinkName);
						$success *= $symlinkCreated;
						if ($symlinkCreated) {
							$this->controller->pd('Created symlink of "' . $packagePublicResourcePath . '" to "' . $symlinkName . '"');
						} else {
							$this->controller->pd('Could not create symlink of "' . $packagePublicResourcePath . '" to "' . $symlinkName . '"');
						}
					}
				}
			}
		}
		return $installedAssets;
	}

	/**
	 * Returns the relative path of the given URI
	 * @param string $uri
	 * @return string
	 */
	public function getRelativePathOfUri($uri) {
		return str_replace($this->getExtensionPath(), '', $uri);
	}

	/**
	 * Returns the array of relative paths that will be used to find assets
	 *
	 * The installer will loop through this paths. If the current path exists
	 * inside the package a symlink to this directory will be created and no
	 * further asset paths will be checked.
	 *
	 * Example:
	 * 	Package: foo/bar
	 * 	Current asset path: Resources/Public/
	 * 	Check if exists: EXT:cundd_composer/vendor/foo/bar/Resources/Public/
	 *  If not move on
	 *  Current asset path: build
	 * 	Check if exists: EXT:cundd_composer/vendor/foo/bar/build
	 *  If exists create symlink to EXT:cundd_composer/vendor/foo/bar/build and break
	 *
	 * @return array<string>
	 */
	public function getAssetPaths() {
		if (!$this->assetPaths) {
			$assetPaths = $this->controller->getConfiguration('assetPaths');
			if ($assetPaths) {
				$assetPaths = explode(',', $assetPaths);
				$assetPaths = array_map(function($path) {
					$path = trim($path);
					return $path;
				}, $assetPaths);
				$this->controller->pd($assetPaths);
				$this->assetPaths = $assetPaths;
			}
		}
		return $this->assetPaths;
	}

	/**
	 * Returns the path to the assets
	 * @return string
	 */
	public function getAssetsDirectoryPath() {
		return $this->getPathToResource() . 'Public/Assets/';
	}

	/**
	 * Create the given directory if it doesn't already exist
	 * @param  string $directory
	 * @return boolean 		Returns TRUE if the directory exists, or could be created, otherwise FALSE
	 */
	public function createDirectoryIfNotExists($directory) {
		// Check if the directory exists
		if (!file_exists($directory)) {
			$permission = 0777;
			if (isset($GLOBALS['TYPO3_CONF_VARS'])
				&& isset($GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask'])
				&& $GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask']) {
				$permission = octdec($GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask']);
			}
			return @mkdir($directory, $permission, TRUE);
		}
		return TRUE;
	}

	/**
	 * Remove all files in the given directory
	 * @param  string $directory
	 * @return boolean            TRUE on success, otherwise FALSE
	 */
	public function removeDirectoryRecursive($directory) {
		$success = TRUE;
		if (!file_exists($directory)) {
			return FALSE;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directory),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ($iterator as $path) {
			if ($path->isLink()) {
				$success *= unlink($path->getPathname());
			} else if ($path->isDir()) {
				$success *= rmdir($path->getPathname());
			} else {
				$success *= unlink($path->getPathname());
			}
		}
		if (is_dir($directory)) {
			rmdir($directory);
		}
		return $success;
	}

	/**
	 * Returns the path to the extensions base
	 *
	 * @return string
	 */
	public function getExtensionPath() {
		return __DIR__ . '/../../';
	}

	/**
	 * Returns the path to the resources folder
	 *
	 * @return string
	 */
	public function getPathToResource() {
		return $this->getExtensionPath() . 'Resources/';
	}

	/**
	 * Returns the path to the temporary directory
	 *
	 * @return string
	 */
	public function getTempPath() {
		return $this->getPathToResource() . 'Private/Temp/';
	}
}