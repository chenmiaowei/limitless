<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/12/5
 * Time: 11:54 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\widgets\ueditor;

use orangins\modules\widgets\assets\AssetBundle;

/**
 * Asset bundle for fancytree Widget
 *
 * @author Wanderson Bragança <wanderson.wbc@gmail.com>
 */
class UEditorAsset extends AssetBundle
{
    /**
     * @var array
     */
    public $js = [
      'ueditor.config.js',
      'ueditor.all.js',
    ];

    /**
     * @var array
     */
    public $depends = [
        'yii\web\JqueryAsset',
    ];

    /**
     * @author 陈妙威
     */
    public function init()
    {
        parent::init();
        $this->sourcePath = __DIR__ . "/resource";
    }
}