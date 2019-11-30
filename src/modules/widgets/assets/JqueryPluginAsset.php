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
class JqueryPluginAsset extends AssetBundle
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
        'js/jquery.plugin.min.js',
    ];
    /**
     * @var array
     */
    public $depends = [
        'orangins\modules\widgets\assets\ResourceAsset',
    ];

    /**
     *
     */
    public function init()
    {
        $this->sourcePath = __DIR__ . "/resource";
        parent::init();
    }
}