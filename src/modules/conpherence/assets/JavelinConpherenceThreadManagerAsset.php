<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/3/22
 * Time: 4:06 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\conpherence\assets;

use orangins\modules\widgets\assets\AssetBundle;

class JavelinConpherenceThreadManagerAsset extends AssetBundle
{
    /**
     * @var array
     */
    public $js = [
        'js/ConpherenceThreadManager.js'
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