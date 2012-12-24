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
 *  the Free Software Foundation; either version 2 of the License, or
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
 * Test case for class Tx_CunddComposer_Domain_Model_Package.
 *
 * @version $Id$
 * @copyright Copyright belongs to the respective authors
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 * @package TYPO3
 * @subpackage Cundd Composer
 *
 * @author Daniel Corn <info@cundd.net>
 */
class Tx_CunddComposer_Domain_Model_PackageTest extends Tx_Extbase_Tests_Unit_BaseTestCase {
	/**
	 * @var Tx_CunddComposer_Domain_Model_Package
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = new Tx_CunddComposer_Domain_Model_Package();
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function getNameReturnsInitialValueForString() { }

	/**
	 * @test
	 */
	public function setNameForStringSetsName() { 
		$this->fixture->setName('Conceived at T3CON10');

		$this->assertSame(
			'Conceived at T3CON10',
			$this->fixture->getName()
		);
	}
	
	/**
	 * @test
	 */
	public function getDescriptionReturnsInitialValueForString() { }

	/**
	 * @test
	 */
	public function setDescriptionForStringSetsDescription() { 
		$this->fixture->setDescription('Conceived at T3CON10');

		$this->assertSame(
			'Conceived at T3CON10',
			$this->fixture->getDescription()
		);
	}
	
	/**
	 * @test
	 */
	public function getVersionReturnsInitialValueForString() { }

	/**
	 * @test
	 */
	public function setVersionForStringSetsVersion() { 
		$this->fixture->setVersion('Conceived at T3CON10');

		$this->assertSame(
			'Conceived at T3CON10',
			$this->fixture->getVersion()
		);
	}
	
	/**
	 * @test
	 */
	public function getTypeReturnsInitialValueForString() { }

	/**
	 * @test
	 */
	public function setTypeForStringSetsType() { 
		$this->fixture->setType('Conceived at T3CON10');

		$this->assertSame(
			'Conceived at T3CON10',
			$this->fixture->getType()
		);
	}
	
	/**
	 * @test
	 */
	public function getHomepageReturnsInitialValueForString() { }

	/**
	 * @test
	 */
	public function setHomepageForStringSetsHomepage() { 
		$this->fixture->setHomepage('Conceived at T3CON10');

		$this->assertSame(
			'Conceived at T3CON10',
			$this->fixture->getHomepage()
		);
	}
	
	/**
	 * @test
	 */
	public function getTimeReturnsInitialValueForDateTime() { }

	/**
	 * @test
	 */
	public function setTimeForDateTimeSetsTime() { }
	
	/**
	 * @test
	 */
	public function getLicenseReturnsInitialValueForInteger() { 
		$this->assertSame(
			0,
			$this->fixture->getLicense()
		);
	}

	/**
	 * @test
	 */
	public function setLicenseForIntegerSetsLicense() { 
		$this->fixture->setLicense(12);

		$this->assertSame(
			12,
			$this->fixture->getLicense()
		);
	}
	
	/**
	 * @test
	 */
	public function getRequireReturnsInitialValueForString() { }

	/**
	 * @test
	 */
	public function setRequireForStringSetsRequire() { 
		$this->fixture->setRequire('Conceived at T3CON10');

		$this->assertSame(
			'Conceived at T3CON10',
			$this->fixture->getRequire()
		);
	}
	
}
?>