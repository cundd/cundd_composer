<?php
$extensionPath = t3lib_extMgm::extPath('cundd_composer');
return array(
	'tx_cunddcomposer_autoloader' 	=> $extensionPath . 'Classes/Autoloader.php',
	'Cundd\\Composer\\Autoloader' 	=> $extensionPath . 'Classes/Autoloader.php'
);
?>