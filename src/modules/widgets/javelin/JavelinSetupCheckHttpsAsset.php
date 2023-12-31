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
 * 检测时区，是否正确设置
 * Class HoverCardAsset
 * @package orangins\modules\widgets\javelin
 * @author 陈妙威
 */
class JavelinSetupCheckHttpsAsset extends JavelinBehaviorAsset
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function behaviorName()
    {
        return 'setup-check-https';
    }

    /**
     * @var array
     */
    public $js = [
        'js/behavious/behavior-setup-check-https.js',
    ];

    /**
     * @var array
     */
    public $depends = [
        'orangins\modules\widgets\javelin\JavelinAsset',
        'orangins\modules\widgets\javelin\JavelinDeviceAsset',
    ];
}