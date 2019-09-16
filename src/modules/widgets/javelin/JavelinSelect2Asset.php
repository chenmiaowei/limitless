<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/27
 * Time: 8:42 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\widgets\javelin;

/**
 * Class JavelinDeviceAsset
 * @package orangins\modules\widgets\javelin
 * @author 陈妙威
 */
class JavelinSelect2Asset extends JavelinBehaviorAsset
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function behaviorName()
    {
        return 'select2';
    }

    /**
     * @var array
     */
    public $js = [
        'js/behavious/behavior-select2.js',
    ];

    /**
     * @var array
     */
    public $depends = [
        'orangins\modules\widgets\javelin\JavelinAsset',
    ];
}