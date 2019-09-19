<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'configPath' => dirname(dirname(__DIR__)) . '/config',
    'scriptsPath' => dirname(dirname(__DIR__)) . '/scripts',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
    ],
];
