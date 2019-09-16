<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 21/05/2017
 * Time: 2:10 PM
 */

namespace orangins\modules\widgets\assets;


/**
 * Class ResourceAsset
 * @package orangins\modules\widgets\javelin
 * @author 陈妙威
 */
class AppAsset extends AssetBundle
{
    public $js = [
        'layout_1/js/app.js',
    ];

    public $css = [
      'css/main.css',
    ];
    /**
     * @var array
     */
    public $depends = [
        'orangins\modules\widgets\assets\ResourceAsset',
    ];

    /**
     * @author 陈妙威
     */
    public function init()
    {
        $this->sourcePath = __DIR__ . "/resource";
        parent::init();
    }
}
