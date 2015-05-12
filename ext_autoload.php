<?php
$extensionPath = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('cundd_composer');
return array(
	'tx_cunddcomposer_autoloader' 	=> $extensionPath . 'Classes/Autoloader.php',
	'Cundd\\Composer\\Autoloader' 	=> $extensionPath . 'Classes/Autoloader.php',
	'Cundd\\CunddComposer\\Autoloader' 	=> $extensionPath . 'Classes/Autoloader.php',
    'tx_cunddcomposer_utility_generalutility' 	=> $extensionPath . 'Classes/Utility/GeneralUtility.php',
);
?>