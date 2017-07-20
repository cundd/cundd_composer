<?php

namespace Cundd\CunddComposer\Controller;

use Cundd\CunddComposer\Domain\Model\Package as Package;
use Cundd\CunddComposer\Utility\ConfigurationUtility as ConfigurationUtility;
use Cundd\CunddComposer\Utility\GeneralUtility as ComposerGeneralUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

class PackageController extends ActionController
{
    /**
     * Package repository
     *
     * @var \Cundd\CunddComposer\Domain\Repository\PackageRepository
     * @inject
     */
    protected $packageRepository;

    /**
     * Asset installer
     *
     * @var \Cundd\CunddComposer\Installer\AssetInstaller
     * @inject
     */
    protected $assetInstaller;

    /**
     * Composer installer
     *
     * @var \Cundd\CunddComposer\Installer\ComposerInstaller
     * @inject
     */
    protected $composerInstaller;

    /**
     * Definition writer
     *
     * @var \Cundd\CunddComposer\Definition\Writer
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
     * @param ViewInterface $view
     */
    protected function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);
        $view->assign(
            'ui',
            [
                'css' => [
                    $this->getResourceUri('Stylesheets/Library/Bootstrap/css/bootstrap.min.css'),
                ],
            ]
        );
    }

    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {
        $this->checkAccess();

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
        $this->checkAccess();

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
        $this->checkAccess();

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
        $this->checkAccess();

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
        $this->checkAccess();

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
        $this->checkAccess();

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
        $this->checkAccess();

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
        $this->checkAccess();

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
        $this->checkAccess();

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
        $this->checkAccess();

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
            . '--verbose '
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
            $installedAssets = [];
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
     * @return string
     */
    protected function getResourceUri($resource)
    {
        $extensionName = $this->controllerContext->getRequest()->getControllerExtensionName();
        $uri = 'EXT:' . GeneralUtility::camelCaseToLowerCaseUnderscored(
                $extensionName
            ) . '/Resources/Public/' . $resource;
        $uri = GeneralUtility::getFileAbsFileName($uri);
        $uri = PathUtility::stripPathSitePrefix($uri);
        if (TYPO3_MODE === 'BE' && $uri !== false) {
            $uri = '../' . $uri;
        }

        return $uri;
    }

    /**
     * @return bool
     */
    private function checkAccess()
    {
        if (isset($GLOBALS['BE_USER']) && $GLOBALS['BE_USER']) {
            return;
        }

        throw new \RuntimeException('Access violation');
    }
}
