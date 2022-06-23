<?php
declare(strict_types=1);

namespace Cundd\CunddComposer;

use Cundd\CunddComposer\Utility\GeneralUtility as ComposerGeneralUtility;

class Autoloader
{
    /**
     * Require the composer autoloader
     *
     * @return void
     */
    static public function register()
    {
        static $didRegister = false;
        if (false === $didRegister) {
            if (!class_exists(ComposerGeneralUtility::class)) {
                require_once __DIR__ . '/Utility/GeneralUtility.php';
            }
            if (file_exists(ComposerGeneralUtility::getPathToVendorDirectory() . 'autoload.php')) {
                include_once(ComposerGeneralUtility::getPathToVendorDirectory() . 'autoload.php');
            }
        }
    }
}

//class_alias(Autoloader::class, 'Cundd\\Composer\\Autoloader');
