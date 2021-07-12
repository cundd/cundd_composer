<?php
declare(strict_types=1);

namespace Cundd\CunddComposer\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Package extends AbstractEntity
{
    /**
     * Name
     *
     * @var string
     * @validate NotEmpty
     */
    protected $name;

    /**
     * Description
     *
     * @var string
     * @validate NotEmpty
     */
    protected $description;

    /**
     * Authors
     *
     * @var string
     */
    protected $authors;

    /**
     * Version
     *
     * @var string
     * @validate NotEmpty
     */
    protected $version;

    /**
     * Type
     *
     * @var string
     * @validate NotEmpty
     */
    protected $type;

    /**
     * Homepage
     *
     * @var string
     */
    protected $homepage;

    /**
     * Time
     *
     * @var \DateTime
     */
    protected $time;

    /**
     * License
     *
     * @var string
     */
    protected $license;

    /**
     * Requirements
     *
     * @var string
     */
    protected $require;

    /**
     * Name of the extension
     *
     * @var string
     */
    protected $package;

    /**
     * Development requirements
     *
     * @var string
     */
    protected $requireDev;

    /**
     * Returns the name
     *
     * @return string $name
     */
    public function getName(): string
    {
        return $this->name;
    }


    /**
     * Returns the description
     *
     * @return string $description
     */
    public function getDescription()
    {
        return $this->description;
    }


    /**
     * Returns the version
     *
     * @return string $version
     */
    public function getVersion()
    {
        return $this->version;
    }


    /**
     * Returns the type
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }


    /**
     * Returns the homepage
     *
     * @return string $homepage
     */
    public function getHomepage()
    {
        return $this->homepage;
    }


    /**
     * Returns the time
     *
     * @return \DateTime $time
     */
    public function getTime()
    {
        return $this->time;
    }


    /**
     * Returns the require
     *
     * @return string $require
     */
    public function getRequire()
    {
        return $this->require;
    }


    /**
     * Returns the license
     *
     * @return string license
     */
    public function getLicense()
    {
        return $this->license;
    }


    /**
     * Returns the requireDev
     *
     * @return string $requireDev
     */
    public function getRequireDev()
    {
        return $this->requireDev;
    }


    /**
     * Returns the authors
     *
     * @return string $authors
     */
    public function getAuthors()
    {
        return $this->authors;
    }


    /**
     * Returns the package
     *
     * @return string $package
     */
    public function getPackage()
    {
        return $this->package;
    }
}
