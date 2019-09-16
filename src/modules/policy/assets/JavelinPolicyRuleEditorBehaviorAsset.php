<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/26
 * Time: 3:07 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\policy\assets;

use orangins\modules\widgets\javelin\JavelinBehaviorAsset;

/**
 * 检测时区，是否正确设置
 * Class HoverCardAsset
 * @package orangins\modules\widgets\javelin
 * @author 陈妙威
 */
class JavelinPolicyRuleEditorBehaviorAsset extends JavelinBehaviorAsset
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function behaviorName()
    {
        return 'policy-rule-editor';
    }

    /**
     * @var array
     */
    public $js = [
        'js/behavior-policy-rule-editor.js',
    ];

    /**
     * @var array
     */
    public $depends = [
        'orangins\modules\typeahead\assets\JavelinPrefabAsset',
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