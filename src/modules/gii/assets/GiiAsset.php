<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace orangins\modules\gii\assets;

use yii\web\AssetBundle;

/**
 * This declares the asset files required by Gii.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class GiiAsset extends AssetBundle
{
    public $js = [
        'js/gii.js',
    ];
    public $depends = [
        'yii\web\YiiAsset'
    ];

    public function init()
    {
        parent::init();

        $this->sourcePath = __DIR__ . "/resource";
    }
}
