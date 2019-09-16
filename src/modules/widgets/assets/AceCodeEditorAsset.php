<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/23
 * Time: 12:01 AM
 */

namespace orangins\modules\widgets\assets;



class AceCodeEditorAsset extends AssetBundle
{
    public $css = [
    ];
    public $js = [
        'global/js/plugins/editors/ace/ace.js'
    ];
    public $depends = [
        'orangins\modules\widgets\assets\ResourceAsset',
    ];

    public function init()
    {
        $this->sourcePath = __DIR__ . "/resource";
        parent::init();
    }
}