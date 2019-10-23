<?php


$params = array_merge(
    require(__DIR__ . '/../../config/common/params.php'),
    require(__DIR__ . '/../../config/common/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-backend',
    'vendorPath' => dirname(__DIR__) . '/../vendor',
    'basePath' => dirname(dirname(__DIR__)) . "/src",
    'language' => 'zh-CN',
    'timeZone' => 'Asia/Shanghai',
    'bootstrap' => ['log', 'context'],
    'defaultRoute' => 'home/index/index',
    'components' => [
        'context' => [
            'class' => 'orangins\lib\components\Context',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'class' => 'orangins\modules\auth\components\UserComponent',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-backend', 'httpOnly' => true],
        ],
        'request' => [
            'class' => 'orangins\lib\request\AphrontRequest',
            'csrfParam' => '_csrf-backend',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'session' => [
            // this is the name of the session cookie used for login on the backend
            'name' => 'advanced-backend',
        ],

        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'],
                ],
            ],
        ],
        'errorHandler' => [
            'class' => 'orangins\lib\error\ErrorHandler',
            'errorAction' => 'meta/index/error',
        ],
        'i18n' => [
            'translations' => [
                'app' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                ],
            ],
        ],
//        'urlManager' => [
//            'enablePrettyUrl' => true,
//            'showScriptName' => false,
//            'enableStrictParsing' => false,
//        ],
    ],
    'params' => $params,
];
