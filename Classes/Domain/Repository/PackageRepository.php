<?php
declare(strict_types=1);

namespace Cundd\CunddComposer\Domain\Repository;

use Cundd\CunddComposer\Domain\Model\Package as Package;
use DomainException;
use SplObjectStorage;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Extbase\Persistence\Repository;
use function array_flip;
use function array_intersect_key;
use function array_keys;
use function array_walk;
use function dirname;
use function file_exists;
use function file_get_contents;
use function implode;
use function is_array;
use function json_decode;
use function json_last_error_msg;
use function method_exists;
use function str_replace;
use const PHP_EOL;

class PackageRepository extends Repository
{
    /**
     * The composer.json contents
     *
     * @var array
     */
    protected $composerJson;

    /**
     * Name of the composer JSON file
     *
     * @var string
     */
    protected $composerFileName = 'cundd_composer.json';

    /**
     * Array of package objects
     *
     * @var SplObjectStorage
     */
    protected $packages = null;

    /**
     * Return all objects of this repository.
     *
     * @return Package[]|SplObjectStorage
     * @api
     */
    public function findAll()
    {
        if (!$this->packages) {
            // Get the package domain object properties
            $properties = array_keys((new Package())->_getProperties());

            $this->packages = new SplObjectStorage();
            $composerJson = $this->getComposerJson();
            foreach ($composerJson as $packageName => $currentJsonData) {
                // Flatten the fields "require" and "authors"
                $this->convertPropertyForKey($currentJsonData, 'authors');
                $this->convertPropertyForKey($currentJsonData, 'require');
                $this->convertPropertyForKey($currentJsonData, 'require-dev', 'requireDev');

                $currentJsonData['package'] = $packageName;

                // Filter the properties
                $currentJsonData = array_intersect_key($currentJsonData, array_flip($properties));

                $package = $this->convert($currentJsonData);
                if ($package) {
                    $this->packages->attach($package);
                }
            }
        }

        return $this->packages;
    }

    /**
     * Convert an array property to a string
     *
     * @param array  $source Reference to the input array
     * @param string $key    The key which to convert
     * @param string $newKey The new key under which to store the converted data
     * @return void
     */
    private function convertPropertyForKey(array &$source, string $key, string $newKey = '')
    {
        if (isset($source[$key])) {
            if (!$newKey) {
                $newKey = $key;
            }
            $originalData = $source[$key];

            array_walk(
                $originalData,
                function (&$value, $key) {
                    $value = $key . ' ' . $value;
                }
            );
            $source[$newKey] = implode(PHP_EOL, $originalData);
        }
    }

    /**
     * Return the list of composer.json files
     *
     * @return string[]
     */
    public function getComposerFiles(): array
    {
        $composerFiles = [];

        /** @var PackageManager $packageManager */
        $packageManager = $this->objectManager->get(PackageManager::class);
        $extensions = $packageManager->getActivePackages();

        foreach ($extensions as $extension) {
            $extensionKey = $extension->getPackageKey();
            $composerFilePath = $extension->getPackagePath() . '/' . $this->composerFileName;
            if (file_exists($composerFilePath)) {
                $composerFiles[$extensionKey] = $composerFilePath;
            }
        }

        return $composerFiles;
    }

    /**
     * Return the composer.json contents as array
     *
     * @param boolean $graceful If set to TRUE no exception will be thrown if a JSON file couldn't be read
     * @return array
     * @throws DomainException if a JSON file couldn't be read
     */
    public function getComposerJson(bool $graceful = false): array
    {
        if (!$this->composerJson) {
            $jsonData = [];
            $composerFiles = $this->getComposerFiles();
            foreach ($composerFiles as $package => $composerFilePath) {
                $relativeComposerFilePath = '../../../../../../'
                    . str_replace(Environment::getPublicPath() . '/', '', dirname($composerFilePath) . '/');

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
                $jsonData[$package] = $this->patchFileAutoloadPaths(
                    $currentJsonData,
                    $relativeComposerFilePath,
                    $composerFilePath,
                    $graceful
                );
            }
            $this->composerJson = $jsonData;
        }

        return $this->composerJson;
    }

    /**
     * Convert the given data to a Package instance
     *
     * @param array $data
     * @return Package Returns an object of type $targetClass
     */
    private function convert(array $data): Package
    {
        $object = new Package();
        if (method_exists($object, '_setProperty')) {
            foreach ($data as $key => $property) {
                $object->_setProperty($key, $property);
            }
        }

        return $object;
    }

    /**
     * Patch the autoload definition
     *
     * @param array  $currentJsonData
     * @param string $relativeComposerFilePath
     * @param string $composerFilePath
     * @param bool   $graceful
     * @return array
     */
    private function patchFileAutoloadPaths(
        array $currentJsonData,
        string $relativeComposerFilePath,
        string $composerFilePath,
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
                                'Exception while adjusting autoload paths in' . $composerFilePath . ': unknown type "' . $autoloadType . '"'
                            );
                        }
                }
            }
        }

        return $currentJsonData;
    }
}
