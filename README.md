Cundd Composer
==============

[Composer](http://getcomposer.org/) support for TYPO3 CMS.


Installation
------------

Upload the extension files to your TYPO3 installation's extension folder and install Cundd Composer as usual through the Extension manager.


Usage
-----

![Cundd Composer icon](https://raw.github.com/cundd/CunddComposer/master/ext_icon.gif "Cundd Composer icon") Icon of the Cundd Composer module

Lets say we want to install the Composer dependencies for an TYPO3 extension called `MyExt`. In the root directory of the extension a valid composer.json file exists.

1. Install `MyExt` through the Extension Manager
2. Go to the `Composer` module in the Tools section
3. Check the preview of the merged composer.json
4. Click on `Merge and install` to tell Composer to install requirements (or `Merge and install development mode` if you want to install development requirements)


For extension developers
------------------------

Place a valid composer.json file in the root directory of your extension. If you want to use the Composer packages in a fronted extension you can simply use the package classes. In case of a backend module you have to register the Cundd Composer autoloader through `Tx_CunddComposer_Autoloader::register()`.


Assets
------

The Asset Installer loops through all the installed composer packages and checks if they contain one of the directories defined in the extension manager (configuration name: `assetPaths`, defaults: `Resources/Public/`, `build`, `lib`, `js`, `font`, `less` and `sass`). If one of the directories exist, a symlink to the directory will be created inside of Cundd Composer's `Resources/Public/Assets/` folder.

Before the Asset Installer can be used, it has to be enabled in the extension manager. Therefore `allowInstallAssets` has to be checked. If `automaticallyInstallAssets` (and `allowInstallAssets`) is enabled the Assets will be installed automatically after Cundd Composer's `install` or `update` function is invoked.


### Example

If the package `foo/bar` contains the directory `Resources/Public/` Cundd Composer will create a symlink at `EXT:cundd_composer/Resources/Public/Assets/foo_bar/` which will point to `EXT:cundd_composer/vendor/foo/bar/Resources/Public/`.


### Aim
The aim of the Asset Installer is to provide a schema to reference asset files and to publish  those files in a public folder, which allows the `vendor` directory to be inaccessible by browsers.