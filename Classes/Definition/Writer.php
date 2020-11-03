<?php
declare(strict_types=1);

namespace Cundd\CunddComposer\Definition;

use Cundd\CunddComposer\Domain\Repository\PackageRepository;
use Cundd\CunddComposer\Utility\GeneralUtility as ComposerGeneralUtility;
use UnexpectedValueException;

class Writer
{
    /**
     * The minimum stability
     * http://getcomposer.org/doc/04-schema.md#minimum-stability
     *
     * @var string
     */
    protected $minimumStability = 'stable';

    /**
     * Package repository
     *
     * @var PackageRepository
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
     * Writer constructor.
     *
     * @param PackageRepository $packageRepository
     */
    public function __construct(PackageRepository $packageRepository)
    {
        $this->packageRepository = $packageRepository;
    }

    /**
     * Write the composer.json file
     *
     * @return boolean Returns TRUE on success, otherwise FALSE
     */
    public function writeMergedComposerJson(): bool
    {
        $composerJson = $this->getMergedComposerJson();
        $composerJson = json_encode($composerJson);
        if ($composerJson) {
            ComposerGeneralUtility::makeSureTempPathExists();

            return (bool)file_put_contents($this->getDestinationFilePath(), $composerJson);
        }

        return false;
    }

    /**
     * Returns the composer.json array merged with the template
     *
     * @param boolean $development Indicates if the dev-requirements should be merged
     * @return array
     * @throws UnexpectedValueException if the composer.json template could not be loaded
     */
    public function getMergedComposerJson(bool $development = false): array
    {
        if (!$this->mergedComposerJson) {
            $composerJson = file_get_contents(
                ComposerGeneralUtility::getPathToResource() . 'Private/Templates/composer.json'
            );
            if (!$composerJson) {
                throw new UnexpectedValueException('Could not load the composer.json template file', 1355952845);
            }
            $composerJson = str_replace('%EXT_PATH%', ComposerGeneralUtility::getExtensionPath(), $composerJson);
            $composerJson = str_replace('%RESOURCE_PATH%', ComposerGeneralUtility::getPathToResource(), $composerJson);
            $composerJson = str_replace('%MINIMUM_STABILITY%', $this->minimumStability, $composerJson);

            $composerJson = json_decode($composerJson, true);

            ComposerGeneralUtility::pd($composerJson, $this->getMergedComposerRequirements());
            $composerJson['require'] = $this->getMergedComposerRequirements();
            $composerJson['autoload'] = $this->getMergedComposerAutoload();
            $composerJson['repositories'] = $this->getMergedComposerData('repositories');

            if ($development || $this->developmentDependencies) {
                $composerJson['require-dev'] = $this->getMergedComposerDevelopmentRequirements();
            }
            if (!isset($composerJson['require-dev']) || !$composerJson['require-dev']) {
                unset($composerJson['require-dev']);
            }

            ComposerGeneralUtility::pd($composerJson);
            $this->mergedComposerJson = $composerJson;
        }

        return $this->mergedComposerJson;
    }

    /**
     * Retrieve the merged composer.json requirements
     *
     * @return string[]
     */
    public function getMergedComposerRequirements(): array
    {
        return $this->getMergedComposerData('require');
    }

    /**
     * Retrieve the merged composer.json development requirements
     *
     * @return string[]
     */
    public function getMergedComposerDevelopmentRequirements(): array
    {
        return $this->getMergedComposerData('require-dev');
    }

    /**
     * Retrieve the merged composer.json autoload settings
     *
     * @return string[]
     */
    public function getMergedComposerAutoload(): array
    {
        return $this->getMergedComposerData('autoload');
    }

    /**
     * Returns if development dependencies should be included
     *
     * @return boolean
     */
    public function getIncludeDevelopmentDependencies(): bool
    {
        return $this->developmentDependencies;
    }

    /**
     * Sets if development dependencies should be included
     *
     * @param boolean $developmentDependencies
     */
    public function setIncludeDevelopmentDependencies(bool $developmentDependencies)
    {
        $this->developmentDependencies = $developmentDependencies;
    }

    /**
     * Returns the minimum stability
     * http://getcomposer.org/doc/04-schema.md#minimum-stability
     *
     * @return string
     */
    public function getMinimumStability(): string
    {
        return $this->minimumStability;
    }

    /**
     * Sets the minimum stability
     * http://getcomposer.org/doc/04-schema.md#minimum-stability
     *
     * @param string $minimumStability
     */
    public function setMinimumStability(string $minimumStability)
    {
        $this->minimumStability = $minimumStability;
    }

    /**
     * Returns the path to the merged composer.json
     *
     * @return string
     */
    public function getDestinationFilePath(): string
    {
        return ComposerGeneralUtility::getTempPath() . 'composer.json';
    }

    /**
     * Returns the merged composer.json data for the given key
     *
     * @param string $key The key for which to merge the data
     * @return array
     */
    protected function getMergedComposerData(string $key): array
    {
        $jsonData = [];
        $composerJson = $this->packageRepository->getComposerJson();
        foreach ($composerJson as $currentJsonData) {
            ComposerGeneralUtility::pd($currentJsonData, isset($currentJsonData[$key]));
            if (isset($currentJsonData[$key])) {
                $mergeData = $currentJsonData[$key];
                if (is_array($mergeData)) {
                    ComposerGeneralUtility::pd($jsonData, $key);
                    $jsonData = ComposerGeneralUtility::arrayMergeRecursive($jsonData, $mergeData, false);
                    ComposerGeneralUtility::pd($jsonData);
                }
            }
        }

        return $jsonData;
    }
}
