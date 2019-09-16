<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/30
 * Time: 1:19 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\widgets\javelin;


/**
 * Class JavelinTokenizerAsset
 * @package orangins\modules\widgets\javelin
 * @author 陈妙威
 */
class JavelinTokenizerAsset extends JavelinBehaviorAsset
{
    /**
     * @var array
     */
    public $js = [
        'js/lib/control/tokenizer/Tokenizer.js',
        'js/behavious/behavior-tokenizer.js',
    ];

    public $css = [
        'css/tokenizer.css'
    ];
    /**
     * @var array
     */
    public $depends = [
        'orangins\modules\typeahead\assets\JavelinPrefabAsset',
    ];

    /**
     * @return string
     * @author 陈妙威
     */
    public function behaviorName()
    {
        return 'aphront-basic-tokenizer';
    }
}