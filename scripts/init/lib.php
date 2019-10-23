<?php

/**
 * @param array $options
 * @throws ReflectionException
 * @throws \yii\base\InvalidConfigException
 * @author 陈妙威
 */
function init_phabricator_script(array $options)
{
    error_reporting(E_ALL | E_STRICT);
    ini_set('display_errors', 1);

    @include_once(__DIR__ . '/../../libphutil/scripts/__init_script__.php');
    @include_once(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');
    require(__DIR__ . '/../../config/common/bootstrap.php');
    require(__DIR__ . '/../../config/console/bootstrap.php');

    phutil_load_library(__DIR__ . '/../../src/');

    $config = yii\helpers\ArrayHelper::merge(
        require(__DIR__ . '/../../config/common/main.php'),
        require(__DIR__ . '/../../config/common/main-local.php'),
        require(__DIR__ . '/../../config/console/main.php'),
        require(__DIR__ . '/../../config/console/main-local.php'),
        $options
    );
    new \orangins\lib\console\Application($config);
}
