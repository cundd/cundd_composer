<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE === 'BE') {

	/**
	 * Registers a Backend Module
	 */
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'Cundd.' . $_EXTKEY,
		'tools', // Make module a submodule of 'tools'
		'composer', // Submodule key
		'', // Position
		array(
			'Package' => 'list, show, new, create, edit, update, delete, install, installAssets, manualInstallation',
		),
		array(
			'access' => 'user,group',
			'icon'   => 'EXT:' . $_EXTKEY . '/ext_icon.gif',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_composer.xml',
		)
	);

}
?>