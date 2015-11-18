<?php
$extensionPath = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('cundd_composer');
return array(
	'Cundd\\Composer\\Autoloader' 	=> $extensionPath . 'Classes/Autoloader.php',
	'Cundd\\CunddComposer\\Autoloader' 	=> $extensionPath . 'Classes/Autoloader.php',
);
