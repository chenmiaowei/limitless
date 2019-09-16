<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/27
 * Time: 8:40 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\aphlict\assets;


use orangins\modules\widgets\javelin\JavelinBehaviorAsset;

/**
 * Class JavelinWorkflowAsset
 * @package orangins\modules\widgets\javelin
 * @author 陈妙威
 */
class JavelinAphlictDropdownBehaviorAsset extends JavelinBehaviorAsset
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function behaviorName()
    {
        return 'aphlict-dropdown';
    }

    /**
     * @var array
     */
    public $js = [
        'js/behavior-aphlict-dropdown.js',
    ];

    /**
     * @var array
     */
    public $depends = [
        'orangins\modules\aphlict\assets\JavelinAphlictAsset',
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