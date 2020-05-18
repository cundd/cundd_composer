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
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name
     *
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
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
     * Sets the description
     *
     * @param string $description
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;
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
     * Sets the version
     *
     * @param string $version
     * @return void
     */
    public function setVersion($version)
    {
        $this->version = $version;
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
     * Sets the type
     *
     * @param string $type
     * @return void
     */
    public function setType($type)
    {
        $this->type = $type;
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
     * Sets the homepage
     *
     * @param string $homepage
     * @return void
     */
    public function setHomepage($homepage)
    {
        $this->homepage = $homepage;
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
     * Sets the time
     *
     * @param \DateTime $time
     * @return void
     */
    public function setTime($time)
    {
        $this->time = $time;
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
     * Sets the require
     *
     * @param string $require
     * @return void
     */
    public function setRequire($require)
    {
        $this->require = $require;
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
     * Sets the license
     *
     * @param string $license
     * @return string license
     */
    public function setLicense($license)
    {
        $this->license = $license;
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
     * Sets the requireDev
     *
     * @param string $requireDev
     * @return void
     */
    public function setRequireDev($requireDev)
    {
        $this->requireDev = $requireDev;
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
     * Sets the authors
     *
     * @param string $authors
     * @return void
     */
    public function setAuthors($authors)
    {
        $this->authors = $authors;
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

    /**
     * Sets the package
     *
     * @param string $package
     * @return void
     */
    public function setPackage($package)
    {
        $this->package = $package;
    }
}
