<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 21/05/2017
 * Time: 2:10 PM
 */

namespace orangins\modules\widgets\fancybox;


use orangins\modules\widgets\assets\AssetBundle;

class FancyboxAsset extends AssetBundle
{
    public $css = [
        'dist/jquery.fancybox.min.css',
    ];
    public $js = [
        'dist/jquery.fancybox.min.js',
        'js/fancybox.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];

    public function init()
    {
        $this->sourcePath = __DIR__ . "/resource";
        parent::init();
    }
}
