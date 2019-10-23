<?php
return [
    'id' => 'app-backend-tests',
    'class' => \orangins\lib\web\Application::className(),
    'components' => [
        'assetManager' => [
            'basePath' => __DIR__ . '/../web/assets',
        ],
        'urlManager' => [
            'showScriptName' => true,
        ],
    ],
];
