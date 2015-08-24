<?php
namespace Cundd\CunddComposer;

/*
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
 */

use Cundd\CunddComposer\Utility\GeneralUtility as ComposerGeneralUtility;

/**
 *
 *
 * @package cundd_composer
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class Autoloader {
	/**
	 * Require the composer autoloader
	 * @return void
	 */
	static public function register() {
		if (file_exists(ComposerGeneralUtility::getPathToVendorDirectory() .  'autoload.php')) {
			include_once(ComposerGeneralUtility::getPathToVendorDirectory() . 'autoload.php');
		}
	}
}
class_alias('Cundd\\CunddComposer\\Autoloader', 'Cundd\\Composer\\Autoloader');