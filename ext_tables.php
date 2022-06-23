<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'CunddComposer',
    'tools',
    'composer',
    '',
    [
        \Cundd\CunddComposer\Controller\PackageController::class => 'list, update, install, installAssets, manualInstallation',
    ],
    [
        'access' => 'user,group',
        'icon'   => 'EXT:cundd_composer/ext_icon.gif',
        'labels' => 'LLL:EXT:cundd_composer/Resources/Private/Language/locallang_composer.xlf',
    ]
);
