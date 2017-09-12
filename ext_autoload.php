<?php
$extensionPath = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('cundd_composer');
return [
    'Cundd\\Composer\\Autoloader'         => $extensionPath . 'Classes/Autoloader.php',
    Cundd\CunddComposer\Autoloader::class => $extensionPath . 'Classes/Autoloader.php',
];
