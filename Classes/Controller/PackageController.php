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
Ir::forceDebug();
ini_set('display_errors', TRUE);

/**
 *
 *
 * @package cundd_composer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class Tx_CunddComposer_Controller_PackageController extends Tx_Extbase_MVC_Controller_ActionController {

	/**
	 * The path to the PHP executable
	 *
	 * @var string
	 */
	protected $phpExecutable = '';

	/**
	 * packageRepository
	 *
	 * @var Tx_CunddComposer_Domain_Repository_PackageRepository
	 */
	protected $packageRepository;

	/**
	 * Enable or disable installation of development dependencies
	 *
	 * @var boolean
	 */
	protected $developmentDependencies = FALSE;

	/**
	 * injectPackageRepository
	 *
	 * @param Tx_CunddComposer_Domain_Repository_PackageRepository $packageRepository
	 * @return void
	 */
	public function injectPackageRepository(Tx_CunddComposer_Domain_Repository_PackageRepository $packageRepository) {
		$this->packageRepository = $packageRepository;
	}

	/**
	 * initializeAction
	 *
	 * @return
	 */
	public function initializeAction() {
		if (isset($this->settings['developmentDependencies'])) {
			$this->developmentDependencies = $this->settings['developmentDependencies'];
		}
	}

	/**
	 * action list
	 *
	 * @return void
	 */
	public function listAction() {
		$composerJson = $this->getMergedComposerJson();

		#Tx_Extbase_Property_PropertyMapper

		$packages = $this->packageRepository->findAll();
		$this->view->assign('packages', $packages);
	}

	/**
	 * Write the composer.json file
	 *
	 * @return boolean Returns TRUE on success, otherwise FALSE
	 */
	public function writeMergedComposerJson() {
		$composerJson = $this->getMergedComposerJson();
		$composerJson = json_encode($composerJson);
		if ($composerJson) {
			return file_put_contents($this->getTempPath() . 'composer.json', $composerJson);
		}
		return FALSE;
	}

	/**
	 * Returns the composer.json array merged with the template
	 *
	 * @return array
	 */
	public function getMergedComposerJson() {
		$composerJson = file_get_contents($this->getPathToResource() . 'Private/Templates/composer.json');
		if (!$composerJson) {
			throw new \UnexpectedValueException('Could not load the composer.json template file', 1355952845);
		}
		$composerJson = str_replace('%EXT_PATH%', $this->getExtensionPath(), $composerJson);
		$composerJson = str_replace('%RESOURCE_PATH%', $this->getPathToResource(), $composerJson);
		Ir::pd($composerJson);
		$composerJson = json_decode($composerJson, TRUE);
		Ir::pd($composerJson);
		$composerJson['require'] = $this->getMergedComposerRequirements();
		return $composerJson;
	}

	/**
	 * Retrieve the merged composer.json requirements
	 *
	 * @return array<string>
	 */
	public function getMergedComposerRequirements() {
		$jsonData = array();
		$composerJson = $this->packageRepository->getComposerJson();
		foreach ($composerJson as $currentJsonData) {
			$jsonData = array_merge($jsonData, $currentJsonData['require']);
		}
		return $jsonData;
	}

	/**
	 * Call composer on the command line to install the dependencies.
	 *
	 * @return string Returns the composer output
	 */
	protected function install() {
		return $this->executeComposerCommand('update');
	}

	/**
	 * Execute the given composer command
	 *
	 * @param string 	$command 	The composer command to execute
	 * @param boolean	$dev 		Call it with the --dev flag
	 * @return string 				Returns the composer output
	 */
	protected function executeComposerCommand($command, $dev = -1) {
		$output = '';
		$pathToComposer = $this->getPathToResource() . '/Private/PHP/composer.phar';

		if ($dev === -1) {
			$dev = $this->developmentDependencies;
		}
		$fullCommand = $this->getPHPExecutable() . ' '
			. '-c ' . php_ini_loaded_file() . ' '
			. '"' . $pathToComposer . '" ' . $command . ' --working-dir "'
			. $this->getTempPath() . '" '
			. '--no-interaction '
			. '--no-ansi '
			# . '--ansi '
			. '--profile '
			. ($dev ? '--dev ' : '')
			. '2>&1';

		$output = shell_exec($fullCommand);

		Ir::pd($output);
		return $output;
	}

	/**
	 * Returns the path to the PHP executable
	 *
	 * @var string
	 * @return
	 */
	public function getPHPExecutable() {
		if (!$this->phpExecutable) {
			if (isset($this->settings['phpExecutable'])) {
				$this->phpExecutable = $this->settings['phpExecutable'];
			} else {
				$this->phpExecutable = $this->getPHPExecutableFromPath();
			}
		}
		return $this->phpExecutable;
	}

	/**
	 * Sets the path to the PHP executable
	 *
	 * @param $phpExecutable
	 * @var string
	 * @return
	 */
	public function setPHPExecutable($phpExecutable) {
		$this->phpExecutable = $phpExecutable;
	}

	/**
	 * Tries to find the PHP executable.
	 *
	 * @return string Returns the path to the PHP executable, or FALSE on error
	 */
	public function getPHPExecutableFromPath() {
		$paths = explode(PATH_SEPARATOR, getenv('PATH'));
		foreach ($paths as $path) {
			// we need this for XAMPP (Windows)
			if (strstr($path, 'php.exe') && isset($_SERVER['WINDIR']) && file_exists($path) && is_file($path)) {
				return $path;
			} else {
				$php_executable = $path . DIRECTORY_SEPARATOR . 'php' . (isset($_SERVER['WINDIR']) ? '.exe' : '');
				if (file_exists($php_executable) && is_file($php_executable)) {
					return $php_executable;
				}
			}
		}
		return FALSE; // not found
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

	/**
	 * action show
	 *
	 * @param Tx_CunddComposer_Domain_Model_Package $package
	 * @return void
	 */
	public function showAction(Tx_CunddComposer_Domain_Model_Package $package) {
		$this->view->assign('package', $package);
	}

	/**
	 * action new
	 *
	 * @param Tx_CunddComposer_Domain_Model_Package $newPackage
	 * @dontvalidate $newPackage
	 * @return void
	 */
	public function newAction(Tx_CunddComposer_Domain_Model_Package $newPackage = NULL) {
		$this->view->assign('newPackage', $newPackage);
	}

	/**
	 * action create
	 *
	 * @param Tx_CunddComposer_Domain_Model_Package $newPackage
	 * @return void
	 */
	public function createAction(Tx_CunddComposer_Domain_Model_Package $newPackage) {
		$this->packageRepository->add($newPackage);
		$this->flashMessageContainer->add('Your new Package was created.');
		$this->redirect('list');
	}

	/**
	 * action edit
	 *
	 * @param Tx_CunddComposer_Domain_Model_Package $package
	 * @return void
	 */
	public function editAction(Tx_CunddComposer_Domain_Model_Package $package) {
		$this->view->assign('package', $package);
	}

	/**
	 * action update
	 *
	 * @param Tx_CunddComposer_Domain_Model_Package $package
	 * @return void
	 */
	public function updateAction(Tx_CunddComposer_Domain_Model_Package $package) {
		$this->packageRepository->update($package);
		$this->flashMessageContainer->add('Your Package was updated.');
		$this->redirect('list');
	}

	/**
	 * action delete
	 *
	 * @param Tx_CunddComposer_Domain_Model_Package $package
	 * @return void
	 */
	public function deleteAction(Tx_CunddComposer_Domain_Model_Package $package) {
		$this->packageRepository->remove($package);
		$this->flashMessageContainer->add('Your Package was removed.');
		$this->redirect('list');
	}

	/**
	 * action install
	 *
	 * @return void
	 */
	public function installAction() {
		$didWriteComposerJson = $this->writeMergedComposerJson();
		$composerOutput = $this->install();
		$this->view->assign('composerOutput', $composerOutput);
	}

}
?>