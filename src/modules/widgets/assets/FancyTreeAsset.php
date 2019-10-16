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
class FancyTreeAsset extends AssetBundle
{
    public $js = [
        'global/js/plugins/trees/fancytree_all.min.js',
    ];

    public $css = [
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
