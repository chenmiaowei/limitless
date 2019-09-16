<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/3/22
 * Time: 3:44 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\widgets\javelin;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\view\phui\PHUITagView;
use orangins\modules\widgets\assets\AssetBundle;

/**
 * Class JavelinBehaviorAsset
 * @package orangins\modules\widgets\javelin
 * @author 陈妙威
 */
abstract class JavelinBehaviorAsset extends AssetBundle
{
    /**
     * @var array
     */
    public $depends = [
        'orangins\modules\widgets\javelin\JavelinAsset',
    ];

    /**
     * @return string
     * @author 陈妙威
     */
    abstract public function behaviorName();

    /**
     * @author 陈妙威
     */
    public function init()
    {
        parent::init();
        $this->sourcePath = __DIR__ . "/resource";
    }

    /**
     * @throws \Exception
     * @author 陈妙威
     */
    public function initExtra()
    {
        $var = PHUITagView::getShadeCode()[PhabricatorEnv::getEnvConfig("ui.widget-color")];
        \Yii::$app->getView()->registerCss(<<<STR
.daterangepicker td.active, .daterangepicker td.active:focus, .daterangepicker td.active:hover {
        background-color: {$var};
}
.ranges ul li.active {
    background-color: {$var};
}
STR
        );
    }
}