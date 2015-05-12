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

use Tx_CunddComposer_Domain_Model_Package as Package;
use Tx_CunddComposer_Utility_GeneralUtility as ComposerGeneralUtility;
use Tx_CunddComposer_Utility_ConfigurationUtility as ConfigurationUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 *
 *
 * @package cundd_composer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class Tx_CunddComposer_Controller_PackageController extends ActionController
{
    /**
     * Package repository
     *
     * @var Tx_CunddComposer_Domain_Repository_PackageRepository
     * @inject
     */
    protected $packageRepository;

    /**
     * Asset installer
     *
     * @var \Tx_CunddComposer_Installer_AssetInstaller
     * @inject
     */
    protected $assetInstaller;

    /**
     * Composer installer
     *
     * @var \Tx_CunddComposer_Installer_ComposerInstaller
     * @inject
     */
    protected $composerInstaller;

    /**
     * Definition writer
     *
     * @var \Tx_CunddComposer_Definition_Writer
     * @inject
     */
    protected $definitionWriter;


    /**
     * Initialize the action
     */
    public function initializeAction()
    {
        if (isset($this->settings['minimum-stability'])) {
            $this->definitionWriter->setMinimumStability($this->settings['minimum-stability']);
        }
    }

    /**
     * Initialize the view
     *
     * @param \TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view
     */
    protected function initializeView(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view)
    {
        parent::initializeView($view);
        $view->assign('ui', array(
            'css' => array(
                $this->getResourceUri('Stylesheets/Library/Bootstrap/css/bootstrap.min.css')
            )
        ));
    }

    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {
        $packages = null;

        try {
            $packages = $this->packageRepository->findAll();
        } catch (\DomainException $exception) {
            $this->view->assign('error', $exception->getMessage());
        }
        $this->view->assign('packages', $packages);

        // Set the development mode to TRUE to see the dev-requirements
        $this->definitionWriter->setIncludeDevelopmentDependencies(true);
        $this->definitionWriter->writeMergedComposerJson();
        $this->assignViewVariables();

        if (!ConfigurationUtility::getPHPExecutable()) {
            $this->view->assign('error', 'PHP executable could not be found');
        }
    }

    /**
     * action to show the manual installation
     *
     * @return void
     */
    public function manualInstallationAction()
    {
        $this->definitionWriter->writeMergedComposerJson();
        $this->assignViewVariables();
    }

    /**
     * action show
     *
     * @param Package $package
     * @return void
     */
    public function showAction(Package $package)
    {
        $this->view->assign('package', $package);
    }

    /**
     * action new
     *
     * @param Package $newPackage
     * @dontvalidate $newPackage
     * @return void
     */
    public function newAction(Package $newPackage = null)
    {
        $this->view->assign('newPackage', $newPackage);
    }

    /**
     * action create
     *
     * @param Package $newPackage
     * @return void
     */
    public function createAction(Package $newPackage)
    {
        $this->packageRepository->add($newPackage);
        $this->addFlashMessage('Your new Package was created.');
        $this->redirect('list');
    }

    /**
     * action edit
     *
     * @param Package $package
     * @return void
     */
    public function editAction(Package $package)
    {
        $this->view->assign('package', $package);
    }

    /**
     * action delete
     *
     * @param Package $package
     * @return void
     */
    public function deleteAction(Package $package)
    {
        $this->packageRepository->remove($package);
        $this->addFlashMessage('Your Package was removed.');
        $this->redirect('list');
    }

    /**
     * action install
     *
     * @param boolean $development Indicates if the dev flag should be specified
     * @return void
     */
    public function installAction($development = false)
    {
        $this->definitionWriter->setIncludeDevelopmentDependencies($development);

        $this->definitionWriter->writeMergedComposerJson();
        $composerOutput = rtrim($this->composerInstaller->install());

        $this->postUpdate();
        $this->view->assign('composerOutput', $composerOutput);
        $this->view->assign('developmentDependencies', $this->definitionWriter->getIncludeDevelopmentDependencies());
    }

    /**
     * action update
     *
     * @param boolean $development Indicates if the dev flag should be specified
     * @return void
     */
    public function updateAction($development = true)
    {
        $this->definitionWriter->setIncludeDevelopmentDependencies($development);

        $this->definitionWriter->writeMergedComposerJson();
        $composerOutput = rtrim($this->composerInstaller->update());

        $this->postUpdate();
        $this->view->assign('composerOutput', $composerOutput);
        $this->view->assign('developmentDependencies', $this->definitionWriter->getIncludeDevelopmentDependencies());
    }

    /**
     * Install the assets
     *
     * @return void
     */
    public function installAssetsAction()
    {
        if (!ConfigurationUtility::getConfiguration('allowInstallAssets')) {
            $this->view->assign('error', 'Asset installation disabled in Extension Manager');
        }
        $this->assetInstaller->setAssetPaths(ConfigurationUtility::getConfiguration('assetPaths'));
        $installedAssets = $this->assetInstaller->installAssets();
        $this->view->assign('installedAssets', $installedAssets);
    }

    /**
     * Assign the view variables
     */
    protected function assignViewVariables()
    {
        $command = 'install';
        ComposerGeneralUtility::makeSureTempPathExists();
        $fullCommand = ConfigurationUtility::getPHPExecutable() . ' '
            . '-c ' . php_ini_loaded_file() . ' '
            . '"' . ComposerGeneralUtility::getComposerPath() . '" ' . $command . ' --working-dir '
            . '"' . ComposerGeneralUtility::getTempPath() . '" '
            . '--no-interaction '
            . '--no-ansi '
            . '--verbose '
            . '--profile '
            . '--dev '
            . '--optimize-autoloader';
        $this->view->assign('manualInstallTip', $fullCommand);

        $this->view->assign('usedPHPBin', ConfigurationUtility::getPHPExecutable());
        $this->view->assign('workingDirectory', ComposerGeneralUtility::getTempPath());
        $this->view->assign('composerPath', ComposerGeneralUtility::getComposerPath());

        // The merged composer install
        $mergedComposerJson = $this->definitionWriter->getMergedComposerJson();
        $mergedComposerJsonString = $this->formatJSON($mergedComposerJson);
        $this->view->assign('mergedComposerJson', $mergedComposerJson);
        $this->view->assign('mergedComposerJsonString', $mergedComposerJsonString);
    }

    /**
     * Invoked after the install/update action
     *
     * @return void
     */
    public function postUpdate()
    {
        if (ConfigurationUtility::getConfiguration('automaticallyInstallAssets')) {
            $this->assetInstaller->setAssetPaths(ConfigurationUtility::getConfiguration('assetPaths'));
            $installedAssets = array();
            if (ConfigurationUtility::getConfiguration('allowInstallAssets')) {
                $installedAssets = $this->assetInstaller->installAssets();
            }
            $this->view->assign('installedAssets', $installedAssets);
        }
    }

    /**
     * Format the given JSON data
     *
     * @param  array $json
     * @return string
     */
    protected function formatJSON($json)
    {
        // Prepare the composer.json to be displayed
        if (defined('JSON_PRETTY_PRINT')) {
            $jsonString = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } else {
            $jsonString = json_encode($json);
            $jsonString = str_replace('\\/', '/', $jsonString);
            $jsonString = str_replace(',', ',' . PHP_EOL, $jsonString);
            $jsonString = str_replace('{', '{' . PHP_EOL, $jsonString);
            $jsonString = str_replace('}', PHP_EOL . '}', $jsonString);
            $jsonString = str_replace('[{', '[' . PHP_EOL . '{', $jsonString);
            $jsonString = str_replace('{{', '{' . PHP_EOL . '{', $jsonString);
            $jsonString = str_replace('}]', '}' . PHP_EOL . ']', $jsonString);
            $jsonString = str_replace('}}', '}' . PHP_EOL . '}', $jsonString);
        }
        $jsonString = rtrim($jsonString);
        return $jsonString;
    }

    /**
     * Returns the URI for the given resource
     *
     * @param string $resource
     * @param bool   $absolute
     * @return string
     */
    protected function getResourceUri($resource, $absolute = false)
    {
        $extensionName = $this->controllerContext->getRequest()->getControllerExtensionName();
        $uri = 'EXT:' . \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName) . '/Resources/Public/' . $resource;
        $uri = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($uri);
        $uri = \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($uri);
        if (TYPO3_MODE === 'BE' && $absolute === false && $uri !== false) {
            $uri = '../' . $uri;
        }
        if ($absolute === true) {
            $uri = $this->request->getBaseURI() . $uri;
        }
        return $uri;
    }

}
