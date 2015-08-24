Cundd Composer
==============

`Composer <http://getcomposer.org/>`_ support for TYPO3 CMS.

Installation
------------

Upload the extension files to your TYPO3 installation's extension folder
and install Cundd Composer as usual through the Extension manager.

Usage
-----

|Cundd Composer icon| Icon of the Cundd Composer module

Lets say we want to install the Composer dependencies for an TYPO3
extension called ``MyExt``. In the root directory of the extension a
valid composer.json file exists.

1. Install ``MyExt`` through the Extension Manager
2. Go to the ``Composer`` module in the Tools section
3. Check the preview of the merged composer.json
4. Click on ``Merge and install`` to tell Composer to install
   requirements (or ``Merge and install development mode`` if you want
   to install development requirements)

For extension developers
------------------------

Place a valid composer.json file in the root directory of your
extension. If you want to use the Composer packages in a fronted
extension you can simply use the package classes. In case of a backend
module you have to register the Cundd Composer autoloader through
``\Cundd\CunddComposer\Autoloader::register()``.

.. |Cundd Composer
icon| image:: https://raw.github.com/cundd/CunddComposer/master/ext_icon.gif
