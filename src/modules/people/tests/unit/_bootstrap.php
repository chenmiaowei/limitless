<?php
/**
 * Here you can initialize variables via \Codeception\Util\Fixtures class
 * to store data in global array and use it in Cests.
 *
 * ```php
 * // Here _bootstrap.php
 * \Codeception\Util\Fixtures::add('user1', ['name' => 'davert']);
 * ```
 *
 * In Cests
 *
 * ```php
 * \Codeception\Util\Fixtures::get('user1');
 * ```
 */


defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');
defined('YII_APP_BASE_PATH') or define('YII_APP_BASE_PATH', __DIR__ . '/../../../../../');


require_once(YII_APP_BASE_PATH . '/libphutil/scripts/__init_script__.php');
require_once(YII_APP_BASE_PATH . '/vendor/autoload.php');
require_once(YII_APP_BASE_PATH . '/vendor/yiisoft/yii2/Yii.php');
require_once(YII_APP_BASE_PATH . '/config/common/bootstrap.php');
require_once(YII_APP_BASE_PATH . '/config/app/bootstrap.php');
phutil_load_library(YII_APP_BASE_PATH . '/src/');


