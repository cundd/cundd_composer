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
     * The path to the PHP executable
     *
     * @var string
     */
    protected $phpExecutable = '';

    /**
     * The minimum stability
     * http://getcomposer.org/doc/04-schema.md#minimum-stability
     *
     * @var string
     */
    protected $minimumStability = 'dev';

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
     * Definition writer
     *
     * @var \Tx_CunddComposer_Definition_Writer
     * @inject
     */
    protected $definitionWriter;

    /**
     * The merged composer.json
     *
     * @var array
     */
    protected $mergedComposerJson;

    /**
     * Enable or disable installation of development dependencies
     *
     * @var boolean
     */
    protected $developmentDependencies = false;

    /**
     * Initialize the action
     */
    public function initializeAction()
    {
        if (isset($this->settings['minimum-stability'])) {
            $this->minimumStability = $this->settings['minimum-stability'];
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
        $this->developmentDependencies = true;
        $this->definitionWriter->writeMergedComposerJson();
        $this->assignViewVariables();

        if (!$this->getPHPExecutable()) {
            $this->view->assign('error', 'PHP executable could not be found');
        }
    }

    /**
     * Assign the view variables
     */
    protected function assignViewVariables()
    {
        $command = 'install';
        $this->definitionWriter->makeSureTempPathExists();
        $fullCommand = $this->getPHPExecutable() . ' '
            . '-c ' . php_ini_loaded_file() . ' '
            . '"' . $this->getComposerPath() . '" ' . $command . ' --working-dir '
            . '"' . Tx_CunddComposer_GeneralUtility::getTempPath() . '" '
            . '--no-interaction '
            . '--no-ansi '
            . '--verbose '
            . '--profile '
            . '--dev '
            . '--optimize-autoloader';
        $this->view->assign('manualInstallTip', $fullCommand);

        $this->view->assign('usedPHPBin', $this->getPHPExecutable());
        $this->view->assign('workingDirectory', Tx_CunddComposer_GeneralUtility::getTempPath());
        $this->view->assign('composerPath', $this->getComposerPath());

        // The merged composer install
        $mergedComposerJson = $this->definitionWriter->getMergedComposerJson();
        $mergedComposerJsonString = $this->formatJSON($mergedComposerJson);
        $this->view->assign('mergedComposerJson', $mergedComposerJson);
        $this->view->assign('mergedComposerJsonString', $mergedComposerJsonString);
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
        $this->developmentDependencies = $development;

        $this->definitionWriter->writeMergedComposerJson();
        $composerOutput = rtrim($this->install());

        $this->postUpdate();
        $this->view->assign('composerOutput', $composerOutput);
        $this->view->assign('developmentDependencies', $this->developmentDependencies);
    }

    /**
     * action update
     *
     * @param boolean $development Indicates if the dev flag should be specified
     * @return void
     */
    public function updateAction($development = true)
    {
        $this->developmentDependencies = $development;

        $this->definitionWriter->writeMergedComposerJson();
        $composerOutput = rtrim($this->update());

        $this->postUpdate();
        $this->view->assign('composerOutput', $composerOutput);
        $this->view->assign('developmentDependencies', $this->developmentDependencies);
    }

    /**
     * Install the assets
     *
     * @return void
     */
    public function installAssetsAction()
    {
        if (!$this->getConfiguration('allowInstallAssets')) {
            $this->view->assign('error', 'Asset installation disabled in Extension Manager');
        }
        $this->assetInstaller->setAssetPaths($this->getConfiguration('assetPaths'));
        $installedAssets = $this->assetInstaller->installAssets();
        $this->view->assign('installedAssets', $installedAssets);
    }

    /**
     * Invoked after the install/update action
     *
     * @return void
     */
    public function postUpdate()
    {
        if ($this->getConfiguration('automaticallyInstallAssets')) {
            $this->assetInstaller->setAssetPaths($this->getConfiguration('assetPaths'));
            $installedAssets = array();
            if ($this->getConfiguration('allowInstallAssets')) {
                $installedAssets = $this->assetInstaller->installAssets();
            }
            $this->view->assign('installedAssets', $installedAssets);
        }
    }

    /**
     * Returns the extension configuration for the given key
     *
     * @param  string $key Configuration key
     * @return mixed      Configuration value
     */
    public function getConfiguration($key)
    {
        static $configuration = null;

        // Read the configuration from the globals
        if ($configuration === null) {
            if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['cundd_composer'])) {
                $configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['cundd_composer']);
            }
        }

        // Return the configuration value
        if ($configuration && isset($configuration[$key])) {
            return $configuration[$key];
        }
        return false;
    }

    /**
     * Call composer on the command line to install the dependencies.
     *
     * @param boolean $dev Argument will be removed in future version
     * @return string                Returns the composer output
     */
    protected function install($dev = false)
    {
        return $this->executeComposerCommand('install', $dev);
    }

    /**
     * Call composer on the command line to update the dependencies.
     *
     * @param boolean $dev Argument will be removed in future version
     * @return string                Returns the composer output
     */
    protected function update($dev = false)
    {
        return $this->executeComposerCommand('update', $dev);
    }

    /**
     * Execute the given composer command
     *
     * @param string  $command The composer command to execute
     * @param boolean $dev     Argument will be removed in future version
     * @return string                Returns the composer output
     */
    protected function executeComposerCommand($command, $dev = false)
    {
        $pathToComposer = $this->getComposerPath();

        if ($dev || func_num_args() > 1) {
            Tx_CunddComposer_GeneralUtility::pd('Argument dev is deprecated and ignored');
        }

        $this->definitionWriter->makeSureTempPathExists();
        $fullCommand = $this->getPHPExecutable() . ' '
//			. '-c ' . php_ini_loaded_file() . ' '
            . '"' . $pathToComposer . '" ' . $command . ' '
            . '--working-dir ' . '"' . Tx_CunddComposer_GeneralUtility::getTempPath() . '" '
//			. '--no-interaction '
//			. '--no-ansi '
            . '--verbose '
//			. '--profile '
//			. '--optimize-autoloader '
//			. ($dev ? '--dev ' : '')
            . '2>&1';

        $fullCommand = 'COMPOSER_HOME=' . Tx_CunddComposer_GeneralUtility::getTempPath() . ' ' . $fullCommand;

        $output = $this->executeShellCommand($fullCommand);

        Tx_CunddComposer_GeneralUtility::pd($fullCommand);
        Tx_CunddComposer_GeneralUtility::pd($output);
        return $output;
    }

    /**
     * Execute the shell command
     *
     * @param string $fullCommand Full composer command
     * @return string
     */
    protected function executeShellCommand($fullCommand)
    {
        $useShellExec = false;
        if ($useShellExec) {
            return shell_exec($fullCommand);
        }

        $output = '';
        $descriptorSpec = array(
            0 => array('pipe', 'r'),  // stdin is a pipe that the child will read from
            1 => array('pipe', 'w'),  // stdout is a pipe that the child will write to
            2 => array('pipe', sys_get_temp_dir() . '/error-output.txt', 'a') // stderr is a file to write to
        );

        $cwd = Tx_CunddComposer_GeneralUtility::getTempPath();
        $env = $_ENV;

        $process = proc_open($fullCommand, $descriptorSpec, $pipes, $cwd, $env);

        if (is_resource($process)) {
            // $pipes now looks like this:
            // 0 => writeable handle connected to child stdin
            // 1 => readable handle connected to child stdout
            // Any error output will be appended to /tmp/error-output.txt

            // fwrite($pipes[0], '<?php print_r($_ENV); ? >');
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            // It is important that you close any pipes before calling
            // proc_close in order to avoid a deadlock
            $returnValue = proc_close($process);

            Tx_CunddComposer_GeneralUtility::pd('Return value:', $returnValue);
        }
        return $output;
    }

    /**
     * Returns the path to the composer phar
     *
     * @return string
     */
    public function getComposerPath()
    {
        return Tx_CunddComposer_GeneralUtility::getPathToResource() . '/Private/PHP/composer.phar';
    }

    /**
     * Returns the path to the PHP executable
     *
     * @return string
     */
    public function getPHPExecutable()
    {
        if (!$this->phpExecutable) {
            $this->phpExecutable = $this->getConfiguration('phpExecutable');
            if (!$this->phpExecutable) {
                if (isset($this->settings['phpExecutable'])) {
                    $this->phpExecutable = $this->settings['phpExecutable'];
                } else {
                    $this->phpExecutable = $this->getPHPExecutableFromPath();
                }
            }
            $this->phpExecutable = trim($this->phpExecutable);
        }
        return $this->phpExecutable;
    }

    /**
     * Sets the path to the PHP executable
     *
     * @param $phpExecutable
     * @return void
     */
    public function setPHPExecutable($phpExecutable)
    {
        $this->phpExecutable = $phpExecutable;
    }

    /**
     * Tries to find the PHP executable.
     *
     * @return string Returns the path to the PHP executable, or FALSE on error
     */
    protected function getPHPExecutableFromPath()
    {
        if (defined('PHP_BINDIR') && file_exists(PHP_BINDIR . '/php') && is_executable(PHP_BINDIR . '/php')) {
            return PHP_BINDIR . '/php';
        }
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
        return false; // not found
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
