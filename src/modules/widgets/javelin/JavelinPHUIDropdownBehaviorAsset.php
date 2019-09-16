<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/26
 * Time: 3:07 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\widgets\javelin;

/**
 * Class HoverCardAsset
 * @package orangins\modules\widgets\javelin
 * @author 陈妙威
 */
class JavelinPHUIDropdownBehaviorAsset extends JavelinBehaviorAsset
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function behaviorName()
    {
        return 'phui-dropdown-menu';
    }

    /**
     * @var array
     */
    public $js = [
        'js/phui/behavior-phui-dropdown-menu.js',
    ];

    /**
     * @var array
     */
    public $depends = [
        'orangins\modules\widgets\javelin\JavelinAsset',
    ];
}