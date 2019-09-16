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
class JavelinDeviceAsset extends JavelinAsset
{
    /**
     * @var array
     */
    public $js = [
        'js/behavious/behavior-device.js',
    ];

    /**
     * @var array
     */
    public $depends = [
        'orangins\modules\widgets\javelin\JavelinAsset',
    ];
}