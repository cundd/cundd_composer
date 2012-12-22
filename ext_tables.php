<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE === 'BE') {

	/**
	 * Registers a Backend Module
	 */
	Tx_Extbase_Utility_Extension::registerModule(
		$_EXTKEY,
		'tools',	 // Make module a submodule of 'tools'
		'composer',	// Submodule key
		'',						// Position
		array(
			'Package' => 'list, show, new, create, edit, update, delete, install',
		),
		array(
			'access' => 'user,group',
			'icon'   => 'EXT:' . $_EXTKEY . '/ext_icon.gif',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_composer.xml',
		)
	);

}

t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'Cundd Composer');

t3lib_extMgm::addLLrefForTCAdescr('tx_cunddcomposer_domain_model_package', 'EXT:cundd_composer/Resources/Private/Language/locallang_csh_tx_cunddcomposer_domain_model_package.xml');
t3lib_extMgm::allowTableOnStandardPages('tx_cunddcomposer_domain_model_package');
$TCA['tx_cunddcomposer_domain_model_package'] = array(
	'ctrl' => array(
		'title'	=> 'LLL:EXT:cundd_composer/Resources/Private/Language/locallang_db.xml:tx_cunddcomposer_domain_model_package',
		'label' => 'name',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'dividers2tabs' => TRUE,

		'versioningWS' => 2,
		'versioning_followPages' => TRUE,
		'origUid' => 't3_origuid',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l10n_parent',
		'transOrigDiffSourceField' => 'l10n_diffsource',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
		),
		'searchFields' => 'name,description,version,type,homepage,time,license,',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'Configuration/TCA/Package.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY) . 'Resources/Public/Icons/tx_cunddcomposer_domain_model_package.gif'
	),
);

?>