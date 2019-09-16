<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/30
 * Time: 1:22 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\typeahead\assets;


use orangins\modules\widgets\javelin\JavelinBehaviorAsset;

/**
 * Class JavelinTypeaheadAsset
 * @package orangins\modules\widgets\javelin
 * @author 陈妙威
 */
class JavelinTypeaheadBrowseAsset extends JavelinBehaviorAsset
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function behaviorName()
    {
        return 'typeahead-browse';
    }

    /**
     * @var array
     */
    public $js = [
        'js/behaviors/behavior-typeahead-browse.js',
    ];

    /**
     * @var array
     */
    public $css = [
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