<?php
$extensionPath = t3lib_extMgm::extPath('cundd_composer');
return array(
	'Tx_CunddComposer_Autoloader' 	=> $extensionPath . 'Classes/Autoloader.php',
	'Cundd\\Composer\\Autoloader' 	=> $extensionPath . 'Classes/Autoloader.php'
);
?>