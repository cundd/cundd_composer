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

use Tx_CunddComposer_Controller_PackageController as PackageController;

/**
 *
 *
 * @package cundd_composer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class Tx_CunddComposer_Definition_Writer
{
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
     * Write the composer.json file
     *
     * @return boolean Returns TRUE on success, otherwise FALSE
     */
    public function writeMergedComposerJson()
    {
        $composerJson = $this->getMergedComposerJson();
        $composerJson = json_encode($composerJson);
        if ($composerJson) {
            $this->makeSureTempPathExists();
            return file_put_contents(Tx_CunddComposer_GeneralUtility::getTempPath() . 'composer.json', $composerJson);
        }
        return false;
    }

    /**
     * Returns the composer.json array merged with the template
     *
     * @param boolean $development Indicates if the dev-requirements should be merged
     * @throws UnexpectedValueException if the composer.json template could not be loaded
     * @return array
     */
    public function getMergedComposerJson($development = false)
    {
        if (!$this->mergedComposerJson) {
            $composerJson = file_get_contents(Tx_CunddComposer_GeneralUtility::getPathToResource() . 'Private/Templates/composer.json');
            if (!$composerJson) {
                throw new UnexpectedValueException('Could not load the composer.json template file', 1355952845);
            }
            $composerJson = str_replace('%EXT_PATH%', Tx_CunddComposer_GeneralUtility::getExtensionPath(), $composerJson);
            $composerJson = str_replace('%RESOURCE_PATH%', Tx_CunddComposer_GeneralUtility::getPathToResource(), $composerJson);
            $composerJson = str_replace('%MINIMUM_STABILITY%', $this->minimumStability, $composerJson);

            $composerJson = json_decode($composerJson, true);

            Tx_CunddComposer_GeneralUtility::pd($composerJson);
            $composerJson['require'] = $this->getMergedComposerRequirements();
            $composerJson['autoload'] = $this->getMergedComposerAutoload();
            $composerJson['repositories'] = $this->getMergedComposerData('repositories');

            if ($development || $this->developmentDependencies) {
                $composerJson['require-dev'] = $this->getMergedComposerDevelopmentRequirements();
            }
            if (!isset($composerJson['require-dev']) || !$composerJson['require-dev']) {
                unset($composerJson['require-dev']);
            }

            Tx_CunddComposer_GeneralUtility::pd($composerJson);
            $this->mergedComposerJson = $composerJson;
        }

        return $this->mergedComposerJson;
    }

    /**
     * Retrieve the merged composer.json requirements
     *
     * @return array<string>
     */
    public function getMergedComposerRequirements()
    {
        return $this->getMergedComposerData('require');
    }

    /**
     * Retrieve the merged composer.json development requirements
     *
     * @return array<string>
     */
    public function getMergedComposerDevelopmentRequirements()
    {
        return $this->getMergedComposerData('require-dev');
    }

    /**
     * Retrieve the merged composer.json autoload settings
     *
     * @return array<string>
     */
    public function getMergedComposerAutoload()
    {
        return $this->getMergedComposerData('autoload');
    }

    /**
     * Returns the merged composer.json data for the given key
     *
     * @param  string $key The key for which to merge the data
     * @return array
     */
    protected function getMergedComposerData($key)
    {
        $jsonData = array();
        $composerJson = $this->packageRepository->getComposerJson();
        foreach ($composerJson as $currentJsonData) {
            if (isset($currentJsonData[$key])) {
                $mergeData = $currentJsonData[$key];
                if (is_array($mergeData)) {
                    $jsonData = Tx_CunddComposer_GeneralUtility::arrayMergeRecursive($jsonData, $mergeData, false);
                }
            }
        }
        return $jsonData;
    }


    /**
     * Make sure that the temporary directory exists
     *
     * @throws RuntimeException if the temporary directory does not exist
     * @return void
     */
    public function makeSureTempPathExists()
    {
        $workingDir = Tx_CunddComposer_GeneralUtility::getTempPath();

        // Check if the working/temporary directory exists
        if (!file_exists($workingDir)) {
            @mkdir($workingDir);

            if (!file_exists($workingDir)) {
                throw new RuntimeException('Working directory "' . $workingDir . '" doesn\'t exists and can not be created',
                    1359541465);
            }
        }
    }
}
