<?php
$composerAutoloaderFile = __DIR__ . '/ext/' . $_EXTKEY . '/vendor/autoload.php';
if (file_exists($composerAutoloaderFile)) {
	require_once($composerAutoloaderFile);
}
?>