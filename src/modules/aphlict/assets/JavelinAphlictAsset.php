<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/30
 * Time: 1:22 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\aphlict\assets;


use orangins\modules\widgets\assets\AssetBundle;

/**
 * Class JavelinTypeaheadAsset
 * @package orangins\modules\widgets\javelin
 * @author 陈妙威
 */
class JavelinAphlictAsset extends AssetBundle
{
    /**
     * @var array
     */
    public $js = [
        'js/Aphlict.js'
    ];

    /**
     * @var array
     */
    public $css = [
    ];

    /**
     * @var array
     */
    public $depends = [
        'orangins\modules\widgets\javelin\JavelinAsset',
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