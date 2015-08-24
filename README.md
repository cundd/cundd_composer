Cundd Composer
==============

[Composer](http://getcomposer.org/) support for TYPO3 CMS.


Installation
------------

Upload the extension files to your TYPO3 installation's extension folder and install Cundd Composer as usual through the Extension manager.


Upgrade to TYPO3 6.2
--------------------

TYPO3 6.2 will parse the extensions `composer.json` files and retrieve different information from it. TYPO3's new Package Manager also allows the definition of dependencies. Unfortunately these dependencies are limited to real TYPO3 extension. If you want to install an extension, which requires a non-TYPO3 package in `composer.json`, the Package Manager tries to resolve this dependency in vain and the installation fails.

To work around this issue cundd_composer will look for the file `cundd_composer.json` instead of `composer.json`. For legacy reasons cundd_composer will still use `composer.json` TYPO3 versions below 6.2. 

**Please check the installed extensions before upgrading to TYPO3 6.2.**


Usage
-----

![Cundd Composer icon](https://raw.github.com/cundd/CunddComposer/master/ext_icon.gif "Cundd Composer icon") Icon of the Cundd Composer module

Lets say we want to install the Composer dependencies for an TYPO3 extension called `MyExt`. In the root directory of the extension a valid `cundd_composer.json` file exists.

1. Install `MyExt` through the Extension Manager
2. Go to the `Composer` module in the Tools section
3. Check the preview of the merged composer.json
4. Click on `Install` or `Update` to tell Composer to install requirements


Command line
------------

TYPO3 based CLI commands are available to manage dependencies.

To find information about the command's available options please run `typo3/cli_dispatch.phpsh extbase help` followed by the command.


### composer:install

Installs the project dependencies from the `composer.lock` file (in `EXT:cundd_composer/Resources/Private/Temp/`) if present, or falls back on the `cundd_composer.json`.

```bash
typo3/cli_dispatch.phpsh extbase composer:install
```


### composer:update

Updates your dependencies to the latest version according to `cundd_composer.json`, and updates the `composer.lock` file (in `EXT:cundd_composer/Resources/Private/Temp/`).

```bash
typo3/cli_dispatch.phpsh extbase composer:update
```


### composer:installassets

Install available assets.

```bash
typo3/cli_dispatch.phpsh extbase composer:installassets
```


### composer:list

List information about the required packages.

```bash
typo3/cli_dispatch.phpsh extbase composer:list
```


For extension developers
------------------------

Place a valid `cundd_composer.json` ([The composer.json Schema](https://getcomposer.org/doc/04-schema.md)) file in the root directory of your extension. If you want to use the Composer packages in a fronted extension you can simply use the package classes. In case of a backend module you have to register the Cundd Composer autoloader through `\Cundd\CunddComposer\Autoloader::register()`.


Assets
------

The Asset Installer loops through all the installed composer packages and checks if they contain one of the directories defined in the extension manager (configuration name: `assetPaths`, defaults: `Resources/Public/`, `build`, `lib`, `js`, `font`, `less` and `sass`). If one of the directories exist, a symlink to the directory will be created inside of Cundd Composer's `Resources/Public/Assets/` folder.

Before the Asset Installer can be used, it has to be enabled in the extension manager. Therefore `allowInstallAssets` has to be checked. If `automaticallyInstallAssets` (and `allowInstallAssets`) is enabled the Assets will be installed automatically after Cundd Composer's `install` or `update` function is invoked.


### Example

If the package `foo/bar` contains the directory `Resources/Public/` Cundd Composer will create a symlink at `EXT:cundd_composer/Resources/Public/Assets/foo_bar/` which will point to `EXT:cundd_composer/vendor/foo/bar/Resources/Public/`.


### Aim
The aim of the Asset Installer is to provide a schema to reference asset files and to publish  those files in a public folder, which allows the `vendor` directory to be inaccessible by browsers.


Sponsored by
------------

[![](http://www.iresults.li/fileadmin/framework/local/img/iresultsLogo.png)](http://www.iresults.li)