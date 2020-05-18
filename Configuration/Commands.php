<?php
declare(strict_types=1);

return [
    'cundd-composer:install'             => ['class' => Cundd\CunddComposer\Command\InstallCommand::class,],
    'cundd-composer:update'              => ['class' => Cundd\CunddComposer\Command\UpdateCommand::class,],
    'cundd-composer:list'                => ['class' => Cundd\CunddComposer\Command\ListCommand::class,],
    'cundd-composer:exec'                => ['class' => Cundd\CunddComposer\Command\ExecCommand::class,],
    'cundd-composer:install-assets'      => ['class' => Cundd\CunddComposer\Command\InstallAssetsCommand::class,],
    'cundd-composer:write-composer-json' => ['class' => Cundd\CunddComposer\Command\WriteComposerJsonCommand::class,],
];
