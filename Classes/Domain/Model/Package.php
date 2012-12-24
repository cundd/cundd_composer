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

/**
 *
 *
 * @package cundd_composer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class Tx_CunddComposer_Domain_Model_Package extends Tx_Extbase_DomainObject_AbstractEntity {

	/**
	 * name
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
	 * @var DateTime
	 */
	protected $time;

	/**
	 * License
	 *
	 * @var integer
	 */
	protected $license;

	/**
	 * Requirements
	 *
	 * @var string
	 */
	protected $require;

	/**
	 * Returns the name
	 *
	 * @return string $name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets the name
	 *
	 * @param string $name
	 * @return void
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Returns the description
	 *
	 * @return string $description
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Sets the description
	 *
	 * @param string $description
	 * @return void
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * Returns the version
	 *
	 * @return string $version
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * Sets the version
	 *
	 * @param string $version
	 * @return void
	 */
	public function setVersion($version) {
		$this->version = $version;
	}

	/**
	 * Returns the type
	 *
	 * @return string $type
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Sets the type
	 *
	 * @param string $type
	 * @return void
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * Returns the homepage
	 *
	 * @return string $homepage
	 */
	public function getHomepage() {
		return $this->homepage;
	}

	/**
	 * Sets the homepage
	 *
	 * @param string $homepage
	 * @return void
	 */
	public function setHomepage($homepage) {
		$this->homepage = $homepage;
	}

	/**
	 * Returns the time
	 *
	 * @return DateTime $time
	 */
	public function getTime() {
		return $this->time;
	}

	/**
	 * Sets the time
	 *
	 * @param DateTime $time
	 * @return void
	 */
	public function setTime($time) {
		$this->time = $time;
	}

	/**
	 * Returns the license
	 *
	 * @return integer $license
	 */
	public function getLicense() {
		return $this->license;
	}

	/**
	 * Sets the license
	 *
	 * @param integer $license
	 * @return void
	 */
	public function setLicense($license) {
		$this->license = $license;
	}

	/**
	 * Returns the require
	 *
	 * @return string $require
	 */
	public function getRequire() {
		return $this->require;
	}

	/**
	 * Sets the require
	 *
	 * @param string $require
	 * @return void
	 */
	public function setRequire($require) {
		$this->require = $require;
	}

}
?>