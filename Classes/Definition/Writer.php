<?php
declare(strict_types=1);

namespace Cundd\CunddComposer\Definition;

use Cundd\CunddComposer\Domain\Repository\PackageRepository;
use Cundd\CunddComposer\Utility\GeneralUtility as ComposerGeneralUtility;
use UnexpectedValueException;
use function file_get_contents;
use function file_put_contents;
use function fwrite;
use function is_array;
use function is_resource;
use function json_decode;
use function json_encode;
use function str_replace;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

class Writer
{
    /**
     * The minimum stability
     * http://getcomposer.org/doc/04-schema.md#minimum-stability
     *
     * @var string
     */
    protected $minimumStability;

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
     * @param resource|null $destination
     * @return boolean Returns TRUE on success, otherwise FALSE
     */
    public function writeMergedComposerJson($destination = null): bool
    {
        $composerJson = $this->getMergedComposerJson();
        $composerJson = json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($composerJson) {
            ComposerGeneralUtility::makeSureTempPathExists();

            return $this->writeToDestination($destination, $composerJson);
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

            $composerData = json_decode($composerJson, true);
            ComposerGeneralUtility::pd($composerData, $this->getMergedComposerRequirements());

            $composerData['require'] = $this->getMergedComposerRequirements();
            $composerData['autoload'] = $this->getMergedComposerAutoload();
            $composerData['repositories'] = $this->getMergedComposerData('repositories');

            if ($development || $this->developmentDependencies) {
                $composerData['require-dev'] = $this->getMergedComposerDevelopmentRequirements();
            }
            if (!isset($composerData['require-dev']) || !$composerData['require-dev']) {
                unset($composerData['require-dev']);
            }
            if (null !== $this->minimumStability) {
                $composerData['minimum-stability'] = $this->minimumStability;
            }

            ComposerGeneralUtility::pd($composerData);
            $this->mergedComposerJson = $composerData;
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
        return $this->getMergedComposerRequirementsData(false);
    }

    /**
     * Retrieve the merged composer.json development requirements
     *
     * @return string[]
     */
    public function getMergedComposerDevelopmentRequirements(): array
    {
        return $this->getMergedComposerRequirementsData(true);
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
    public function getDefaultDestinationFilePath(): string
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

    /**
     * Return the merged `require` or `require-dev` data from composer.json
     *
     * @param bool $dev
     * @return array
     */
    private function getMergedComposerRequirementsData(bool $dev): array
    {
        $key = $dev ? 'require-dev' : 'require';
        $requirements = [];
        $composerJson = $this->packageRepository->getComposerJson();
        foreach ($composerJson as $currentJsonData) {
            if (isset($currentJsonData[$key])) {
                $requirementsToMerge = $currentJsonData[$key];
                if (!is_array($requirementsToMerge)) {
                    // There appears to be an error in the schema -> skip this entry
                    continue;
                }

                $requirements = $this->mergeRequirementsFromExtension($requirements, $requirementsToMerge);
            }
        }

        return $requirements;
    }

    /**
     * Merge the requirements from an extension into the collected requirements
     *
     * The versions are not compared, but the last `version != *` wins
     *
     * @param array $collectedRequirements
     * @param array $requirementsFromExtension
     * @return array
     */
    private function mergeRequirementsFromExtension(
        array $collectedRequirements,
        array $requirementsFromExtension
    ): array {
        foreach ($requirementsFromExtension as $package => $versionConstraints) {
            if (!isset($collectedRequirements[$package])) {
                // Register the new requirement
                $collectedRequirements[$package] = $versionConstraints;
            } elseif ($requirementsFromExtension[$package] !== '*') {
                // Overwrite the old requirement if the current requirement isn't a wildcard
                $collectedRequirements[$package] = $versionConstraints;
            }
        }

        return $collectedRequirements;
    }

    private function writeToDestination($destination, string $composerJson): bool
    {
        if (is_resource($destination)) {
            return 0 < fwrite($destination, $composerJson);
        } else {
            return (bool)file_put_contents($this->getDefaultDestinationFilePath(), $composerJson);
        }
    }
}
