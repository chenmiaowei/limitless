<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 21/05/2017
 * Time: 2:10 PM
 */

namespace orangins\modules\widgets\assets;


/**
 * Class BaseResourceAsset
 * @package orangins\modules\widgets\assets
 * @author 陈妙威
 */
class BaseResourceAsset extends AssetBundle
{
    /**
     * @var array
     */
    public $css = [
        'global/css/icons/icomoon/styles.css',
        'global/css/icons/fontawesome/styles.min.css',
        'layout_1/css/bootstrap.min.css',
        'layout_1/css/bootstrap_limitless.min.css',
        'layout_1/css/layout.min.css',
        'layout_1/css/components.min.css',
        'layout_1/css/colors.min.css',
    ];
    /**
     * @var array
     */
    public $js = [
        'js/underscore-min.js',
        'global/js/main/bootstrap.bundle.min.js',
        'global/js/plugins/loaders/blockui.min.js',
        'global/js/plugins/ui/ripple.min.js',
    ];
    /**
     * @var array
     */
    public $depends = [
        'yii\web\YiiAsset',
        'orangins\modules\widgets\javelin\JavelinAsset',
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
