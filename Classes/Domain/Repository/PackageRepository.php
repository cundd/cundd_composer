<?php
declare(strict_types=1);

namespace Cundd\CunddComposer\Domain\Repository;

use Cundd\CunddComposer\Domain\Model\Package as Package;
use DomainException;
use SplFileInfo;
use SplObjectStorage;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Extbase\Persistence\Repository;
use function array_flip;
use function array_intersect_key;
use function array_keys;
use function array_walk;
use function file_exists;
use function file_get_contents;
use function implode;
use function is_array;
use function json_decode;
use function json_last_error;
use function method_exists;
use function str_replace;

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
     * Returns all objects of this repository.
     *
     * @return Package[]|SplObjectStorage
     * @api
     */
    public function findAll()
    {
        if (!$this->packages) {
            // Get the package domain object properties
            $properties = new Package();
            $properties = array_keys($properties->_getProperties());

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
     * Converts an array property to a string
     *
     * @param array  $source Reference to the input array
     * @param string $key    The key which to convert
     * @param string $newKey The new key under which to store the converted data
     * @return void
     */
    protected function convertPropertyForKey(&$source, $key, $newKey = '')
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
     * Returns the list of composer.json files
     *
     * @return array<string>
     */
    public function getComposerFiles()
    {
        $composerFiles = [];

        /** @var \TYPO3\CMS\Core\Package\PackageManager $packageManager */
        $packageManager = $this->objectManager->get('TYPO3\\CMS\\Core\\Package\\PackageManager');
        $extensions = $packageManager->getActivePackages();

        /** @var \TYPO3\CMS\Core\Package\Package $extension */
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
     * Returns the composer.json contents as array
     *
     * @param boolean $graceful If set to TRUE no exception will be thrown if a JSON file couldn't be read
     * @return array
     * @throws DomainException if a JSON file couldn't be read
     */
    public function getComposerJson($graceful = false)
    {
        if (!$this->composerJson) {
            $jsonData = [];
            $composerFiles = $this->getComposerFiles();
            foreach ($composerFiles as $package => $composerFilePath) {
                $composerFile = new SplFileInfo($composerFilePath);
                $relativeComposerFilePath = '../../../../../../' . str_replace(Environment::getPublicPath() . '/', '', $composerFile->getPath());

                $currentJsonData = null;
                $jsonString = file_get_contents($composerFilePath);

                if ($jsonString) {
                    $currentJsonData = json_decode($jsonString, true);
                }
                if (!$currentJsonData && !$graceful) {
                    throw new DomainException(
                        'Exception while parsing composer file ' . $composerFilePath . ': '
                        . $this->getJsonErrorDescription(),
                        1356356009
                    );
                }

                // Merge the autoload definition
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
                $jsonData[$package] = $currentJsonData;
            }
            $this->composerJson = $jsonData;
        }

        return $this->composerJson;
    }

    /**
     * Returns an error description for the last JSON error
     *
     * @return string
     */
    protected function getJsonErrorDescription()
    {
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                $error = 'No errors';
                break;
            case JSON_ERROR_DEPTH:
                $error = 'Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $error = 'Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $error = 'Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                $error = 'Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                $error = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                $error = 'Unknown error';
                break;
        }

        return $error;
    }

    /**
     * Converts the given data to a Package instance
     *
     * @param  array  $data
     * @return Package Returns an object of type $targetClass
     */
    protected function convert($data)
    {
        $object = new Package();
        if (method_exists($object, '_setProperty')) {
            foreach ($data as $key => $property) {
                $object->_setProperty($key, $property);
            }
        }

        return $object;
    }
}
