<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/21
 * Time: 11:33 PM
 */

namespace orangins\modules\widgets\assets;


/**
 * Class FormCheckboxAsset
 * @package orangins\modules\widgets\assets
 */
class FormCountdownAsset extends AssetBundle
{
    /**
     * @var array
     */
    public $css = [
    ];
    /**
     * @var array
     */
    public $js = [
        'js/jquery.countdown.min.js',
        'js/jquery.countdown-zh-CN.js',
    ];
    /**
     * @var array
     */
    public $depends = [
        'orangins\modules\widgets\assets\JqueryPluginAsset',
    ];

    /**
     *
     */
    public function init()
    {
        $this->sourcePath = __DIR__ . "/resource";
        parent::init(); // TODO: Change the autogenerated stub
    }
}