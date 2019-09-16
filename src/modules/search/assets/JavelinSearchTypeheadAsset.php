<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/30
 * Time: 1:35 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\search\assets;

use orangins\modules\widgets\javelin\JavelinBehaviorAsset;

/**
 * Class JavelinPrefabAsset
 * @package orangins\modules\widgets\javelin
 * @author 陈妙威
 */
class JavelinSearchTypeheadAsset extends JavelinBehaviorAsset
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function behaviorName()
    {
        return 'phabricator-search-typeahead';
    }

    /**
     * @var array
     */
    public $js = [
        'js/behavior-search-typeahead.js',
    ];

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