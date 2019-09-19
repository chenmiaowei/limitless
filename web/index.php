<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require(__DIR__ . '/../libphutil/src/__phutil_library_init__.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/../config/common/bootstrap.php');
require(__DIR__ . '/../config/app/bootstrap.php');

phutil_load_library(__DIR__ . '/../src');

$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/../config/common/main.php'),
    require(__DIR__ . '/../config/common/main-local.php'),
    require(__DIR__ . '/../config/app/main.php'),
    require(__DIR__ . '/../config/app/main-local.php')
);

(new orangins\lib\web\Application($config))->run();
