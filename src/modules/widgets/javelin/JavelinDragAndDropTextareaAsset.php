<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/27
 * Time: 8:40 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\widgets\javelin;


/**
 * Class JavelinWorkflowAsset
 * @package orangins\modules\widgets\javelin
 * @author 陈妙威
 */
class JavelinDragAndDropTextareaAsset extends JavelinBehaviorAsset
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function behaviorName()
    {
        return 'aphront-drag-and-drop-textarea';
    }

    /**
     * @var array
     */
    public $js = [
        'js/behavious/behavior-drag-and-drop-textarea.js',
    ];

    /**
     * @var array
     */
    public $depends = [
        'orangins\modules\widgets\javelin\JavelinGlobalDragAndDropAsset',
    ];
}