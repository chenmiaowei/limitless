<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/27
 * Time: 8:40 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\widgets\ueditor;


use orangins\modules\widgets\javelin\JavelinBehaviorAsset;

/**
 * Class JavelinWorkflowAsset
 * @package orangins\modules\widgets\javelin
 * @author 陈妙威
 */
class JavelinUEditorBehaviorAsset extends JavelinBehaviorAsset
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function behaviorName()
    {
        return 'ueditor-control';
    }

    /**
     * @var array
     */
    public $js = [
        'behavior-ueditor-control.js',
    ];

    /**
     * @var array
     */
    public $depends = [
        'orangins\modules\widgets\javelin\JavelinAsset',
        'orangins\modules\widgets\ueditor\UEditorAsset',
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