<?php
declare(strict_types=1);

namespace Cundd\CunddComposer\Service;

use Cundd\CunddComposer\Domain\Model\Package;
use Cundd\CunddComposer\Utility\GeneralUtility as ComposerGeneralUtility;
use DomainException;
use SplObjectStorage;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\PackageManager;
use function dirname;
use function file_exists;
use function file_get_contents;
use function is_array;
use function json_decode;
use function json_last_error_msg;
use function strpos;

class PackageCollectorService
{
    /**
     * @var PackageManager
     */
    private $packageManager;

    /**
     * Package Collector Service constructor
     *
     * @param PackageManager $packageManager
     */
    public function __construct(PackageManager $packageManager)
    {
        $this->packageManager = $packageManager;
    }

    /**
     * Collect all Packages
     *
     * @return Package[]
     */
    public function collectPackages(): iterable
    {
        $packages = new SplObjectStorage();
        $composerJson = $this->getMergedComposerJson();
        foreach ($composerJson as $packageName => $currentJsonData) {
            $currentJsonData['package'] = $packageName;

            $packages->attach(Package::fromProperties($currentJsonData));
        }

        return $packages;
    }

    /**
     * Return the Composer definitions as array
     *
     * @param boolean $graceful If set to TRUE no exception will be thrown if a JSON file couldn't be read
     * @return array
     * @throws DomainException if a JSON file couldn't be read
     */
    public function getMergedComposerJson(bool $graceful = false): array
    {
        $jsonData = [];
        $packageToComposerFiles = $this->collectComposerFiles();
        foreach ($packageToComposerFiles as $extensionKey => $files) {
            if (!$files['cundd_composer'] && !$files['composer']) {
                continue;
            }

            // Load the cundd_composer.json if it was found
            $currentJsonData = $files['cundd_composer'] ? $this->readJsonData($files['cundd_composer'], $graceful) : [];

            // Native composer.json
            if ($files['composer']) {
                $nativeComposerJsonData = $this->readJsonData($files['composer'], $graceful);

                if ($this->isMarkedForInstallation($nativeComposerJsonData)) {
                    $currentJsonData = ComposerGeneralUtility::arrayMergeRecursive(
                        $currentJsonData,
                        $nativeComposerJsonData,
                        false
                    );
                }
            }

            if (!empty($currentJsonData)) {
                $jsonData[$extensionKey] = $this->patchFileAutoloadPaths(
                    $currentJsonData,
                    $this->buildRelativeComposerFilePath($files),
                    $extensionKey,
                    $graceful
                );
            }
        }

        return $jsonData;
    }

    /**
     * Return the map of TYPO3 Packages to their composer.json and cundd_composer.json files
     *
     * @psalm-param (string|null)[][]
     * @return string[][]|null[][]
     */
    private function collectComposerFiles(): array
    {
        $composerFiles = [];

        foreach ($this->packageManager->getActivePackages() as $extension) {
            $packagePath = $extension->getPackagePath();
            $isCorePackage = strpos($packagePath, '/typo3/sysext/') !== false;

            if (!$isCorePackage) {
                $extensionKey = $extension->getPackageKey();
                $composerFilePath = $packagePath . 'composer.json';
                $cunddComposerFilePath = $packagePath . 'cundd_composer.json';

                $composerFiles[$extensionKey] = [
                    'composer'       => file_exists($composerFilePath) ? $composerFilePath : null,
                    'cundd_composer' => file_exists($cunddComposerFilePath) ? $cunddComposerFilePath : null,
                ];
            }
        }

        return $composerFiles;
    }

    /**
     * Patch the 'autoload' definition
     *
     * @param array  $currentJsonData
     * @param string $relativeComposerFilePath
     * @param string $extensionKey
     * @param bool   $graceful
     * @return array
     */
    private function patchFileAutoloadPaths(
        array $currentJsonData,
        string $relativeComposerFilePath,
        string $extensionKey,
        bool $graceful
    ): array {
        if (isset($currentJsonData['autoload']) && is_array($currentJsonData['autoload'])) {
            foreach ($currentJsonData['autoload'] as $autoloadType => $autoLoadConfig) {
                switch ($autoloadType) {
                    case 'classmap':
                    case 'psr-0':
                    case 'psr-4':
                    case 'files';
                        foreach ($autoLoadConfig as $pathKey => $pathOrFile) {
                            $autoLoadConfig[$pathKey] = $relativeComposerFilePath . $pathOrFile;
                        }
                        $currentJsonData['autoload'][$autoloadType] = $autoLoadConfig;
                        break;
                    default:
                        if (!$graceful) {
                            throw new DomainException(
                                'Exception while adjusting autoload paths for package ' . $extensionKey . ': unknown type "' . $autoloadType . '"'
                            );
                        }
                }
            }
        }

        return $currentJsonData;
    }

    /**
     * @param string $composerFilePath
     * @param bool   $graceful
     * @return mixed|null
     */
    private function readJsonData(string $composerFilePath, bool $graceful)
    {
        $currentJsonData = null;
        $jsonString = file_get_contents($composerFilePath);

        if ($jsonString) {
            $currentJsonData = json_decode($jsonString, true);
        }
        if (!$currentJsonData && !$graceful) {
            throw new DomainException(
                'Exception while parsing composer file ' . $composerFilePath . ': '
                . json_last_error_msg(),
                1356356009
            );
        }

        return $currentJsonData;
    }

    private function isMarkedForInstallation(array $nativeComposerJsonData): bool
    {
        $extra = $nativeComposerJsonData['extra'] ?? [];
        if (!isset($extra['cundd/composer']) || !is_array($extra['cundd/composer'])) {
            return false;
        }

        return (bool)($extra['cundd/composer']['install'] ?? false);
    }

    /**
     * @param array $files
     * @return string
     */
    private function buildRelativeComposerFilePath(array $files): string
    {
        return '../../../../../../'
            . str_replace(
                Environment::getPublicPath() . '/',
                '',
                dirname($files['cundd_composer'] ?? $files['composer']) . '/'
            );
    }
}
