<?php
declare(strict_types=1);

namespace Cundd\CunddComposer\Domain\Repository;

use Cundd\CunddComposer\Domain\Model\Package as Package;
use Cundd\CunddComposer\Service\PackageCollectorService;
use DomainException;
use SplObjectStorage;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class PackageRepository extends Repository
{
    /**
     * The composer.json contents
     *
     * @var array
     */
    protected $composerJson;

    /**
     * Array of package objects
     *
     * @var SplObjectStorage
     */
    protected $packages = null;

    /**
     * @var PackageCollectorService
     */
    private $packageCollectorService;

    /**
     * Package Repository constructor
     *
     * @param ObjectManagerInterface  $objectManager
     * @param PackageCollectorService $packageCollectorService
     */
    public function __construct(ObjectManagerInterface $objectManager, PackageCollectorService $packageCollectorService)
    {
        parent::__construct($objectManager);
        $this->packageCollectorService = $packageCollectorService;
    }

    /**
     * Return all objects of this repository.
     *
     * @return Package[]|SplObjectStorage
     */
    public function findAll()
    {
        if (!$this->packages) {
            $this->packages = $this->packageCollectorService->collectPackages();
        }

        return $this->packages;
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
            $this->composerJson = $this->packageCollectorService->getMergedComposerJson($graceful);
        }

        return $this->composerJson;
    }
}
