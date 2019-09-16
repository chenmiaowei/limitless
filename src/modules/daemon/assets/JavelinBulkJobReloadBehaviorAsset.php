<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/30
 * Time: 1:35 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\daemon\assets;

use orangins\modules\widgets\javelin\JavelinBehaviorAsset;

/**
 * Class JavelinPrefabAsset
 * @package orangins\modules\widgets\javelin
 * @author 陈妙威
 */
class JavelinBulkJobReloadBehaviorAsset extends JavelinBehaviorAsset
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function behaviorName()
    {
        return 'bulk-job-reload';
    }

    /**
     * @var array
     */
    public $js = [
        'behavior-bulk-job-reload.js',
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