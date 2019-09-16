<?php

use orangins\lib\env\PhabricatorEnv;

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');
defined('YII_APP_BASE_PATH') or define('YII_APP_BASE_PATH', __DIR__.'/../../../../');

require_once(__DIR__ . '/../../../../libphutil/scripts/__init_script__.php');
require_once(YII_APP_BASE_PATH . '/vendor/autoload.php');
require_once(YII_APP_BASE_PATH . '/vendor/yiisoft/yii2/Yii.php');
require_once(YII_APP_BASE_PATH . '/config/common/bootstrap.php');
require_once(YII_APP_BASE_PATH . '/config/app/bootstrap.php');
phutil_load_library(__DIR__ . '/../../../../src/');