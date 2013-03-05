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



/**
 *
 *
 * @package cundd_composer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class Tx_CunddComposer_Installer_AssetInstaller {
	/**
	 * @var Tx_CunddComposer_Controller_PackageController
	 */
	protected $controller = NULL;

	/**
	 * An array of paths to look for assets inside the installed packages
	 * @var array
	 */
	protected $assetPaths = array(
		'Resources/Public/',
		'build'
	);

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
	 * @param Tx_CunddComposer_Controller_PackageController $controller
	 * @return array<array<string>>	Returns an array of installed assets
	 */
	public function installAssets($controller) {
		$this->controller = $controller;
		$success = TRUE;
		$installedAssets = array();
		$mergedComposerJson = $controller->getMergedComposerJson();

		$vendorDirectory = $this->getExtensionPath() . 'vendor/';
		$assetsDirectoryPath = $this->getAssetsDirectoryPath();

		// Remove the old links
		$this->removeFilesInDirectory($assetsDirectoryPath);
		$this->createDirectoryIfNotExists($assetsDirectoryPath);
		if (!file_exists($assetsDirectoryPath)) {
			throw new \RuntimeException('Directory "' . $assetsDirectoryPath . '" doesn\'t exists and can not be created', 1362514209);
		}
		#echo $this->getAssetsDirectoryPath(). __LINE__ . ':' . t3lib_div::rmdir($this->getAssetsDirectoryPath(), TRUE);
		#echo __LINE__ . ':' . t3lib_div::mkdir($this->getAssetsDirectoryPath());

		// foreach ($mergedComposerJson as $packageName => $composerJson) {
		foreach ($mergedComposerJson['require'] as $package => $version) {
			$packagePublicResourcePath = 'Not found';
			$packagePath = $vendorDirectory . $package . DIRECTORY_SEPARATOR;
			$symlinkName = $assetsDirectoryPath . str_replace(DIRECTORY_SEPARATOR, '_', $package);

			foreach ($this->assetPaths as $assetPaths) {
				$packagePublicResourcePath = $packagePath . $assetPaths;

				// Check if the public resource folders exist
				if (file_exists($packagePublicResourcePath)) {
					// Add the asset information to the array
					$installedAssets[$package] = array(
						'name' 		=> $package,
						'version' 	=> $version,
						'source' 	=> $packagePublicResourcePath,
						'target' 	=> $symlinkName
					);

					// Create the symlink if it doesn't exist
					if (!file_exists($symlinkName)) {
						$symlinkCreated = symlink($packagePublicResourcePath, $symlinkName);
						$success *= $symlinkCreated;
						if (!$symlinkCreated) {
							$controller->pd('Could not create symlink of "' . $packagePublicResourcePath . '" to "' . $symlinkName . '"');
						}
					}
				}
			}
		}
		return $installedAssets;
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
			return @mkdir($directory);
		}
		return TRUE;
	}

	/**
	 * Remove all files in the given directory
	 * @param  string $directory
	 * @return boolean            TRUE on success, otherwise FALSE
	 */
	public function removeFilesInDirectory($directory) {
		$success = TRUE;
		// $this->controller->pd($directory);
		// $this->controller->pd(`ls -hal $directory`);
		if (!file_exists($directory)) {
			return FALSE;
		}
		foreach (new DirectoryIterator($directory) as $fileInfo) {
			if ($fileInfo->isDot()) continue;
			if ($fileInfo->isLink()) {
				$success *= unlink($fileInfo->getPathname());
			}
		}
		rmdir($directory);
		// $this->controller->pd(`ls -hal $directory`);
		return (bool) $success;
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