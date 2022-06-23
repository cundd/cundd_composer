<?php
declare(strict_types=1);

namespace Cundd\CunddComposer\Domain\Model;

class Package
{
    /**
     * Name
     *
     * @var string
     */
    private string $name;

    /**
     * Name of the extension
     *
     * @var string
     */
    private string $package;

    /**
     * Description
     *
     * @var string
     */
    private string $description;

    /**
     * Authors
     *
     * @var array
     */
    private array $authors;

    /**
     * Version
     *
     * @var string|null
     */
    private ?string $version;

    /**
     * Type
     *
     * @var string|null
     */
    private ?string $type;

    /**
     * Homepage
     *
     * @var string|null
     */
    private ?string $homepage;

    /**
     * Time
     *
     * @var string|null
     */
    private ?string $time;

    /**
     * License
     *
     * @var string|null
     */
    private ?string $license;

    /**
     * Requirements
     *
     * @var array
     */
    private array $require;

    /**
     * Development requirements
     *
     * @var array
     */
    private array $requireDev;

    private function __construct(
        string $package,
        string $name,
        ?string $description,
        ?string $type,
        array $require,
        array $requireDev,
        array $authors,
        ?string $version,
        ?string $homepage,
        ?string $time,
        ?string $license
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->authors = $authors;
        $this->version = $version;
        $this->type = $type;
        $this->homepage = $homepage;
        $this->time = $time;
        $this->license = $license;
        $this->require = $require;
        $this->package = $package;
        $this->requireDev = $requireDev;
    }

    public static function fromProperties(array $properties): self
    {
        return new static(
            $properties['package'],
            $properties['name'],
            $properties['description'] ?? null,
            $properties['type'] ?? null,
            $properties['require'] ?? [],
            $properties['require-dev'] ?? [],
            $properties['authors'] ?? [],
            $properties['version'] ?? null,
            $properties['homepage'] ?? null,
            $properties['time'] ?? null,
            $properties['license'] ?? null,
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getHomepage(): ?string
    {
        return $this->homepage;
    }

    public function getTime(): ?string
    {
        return $this->time;
    }

    public function getRequire(): array
    {
        return $this->require;
    }

    public function getLicense(): ?string
    {
        return $this->license;
    }

    public function getRequireDev(): array
    {
        return $this->requireDev;
    }

    public function getAuthors(): array
    {
        return $this->authors;
    }

    public function getPackage(): ?string
    {
        return $this->package;
    }
}
