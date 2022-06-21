<?php

$EM_CONF[$_EXTKEY] = [
    'title'            => 'Cundd Composer',
    'description'      => 'Composer support for TYPO3 CMS (https://github.com/cundd/CunddComposer)',
    'category'         => 'module',
    'author'           => 'Daniel Corn',
    'author_email'     => 'info@cundd.net',
    'author_company'   => 'cundd',
    'state'            => 'stable',
    'clearCacheOnLoad' => 0,
    'version'          => '5.0.1-dev',
    'constraints'      => [
        'depends'   => [
            'typo3' => '9.5.0-10.4.99',
        ],
        'conflicts' => [
        ],
        'suggests'  => [
        ],
    ],
];
