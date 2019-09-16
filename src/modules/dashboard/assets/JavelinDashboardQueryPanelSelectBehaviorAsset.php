<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/30
 * Time: 1:35 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\dashboard\assets;

use orangins\modules\widgets\javelin\JavelinBehaviorAsset;

/**
 * Class JavelinReorderQueriesAsset
 * @package orangins\modules\widgets\javelin
 * @author 陈妙威
 */
class JavelinDashboardQueryPanelSelectBehaviorAsset extends JavelinBehaviorAsset
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function behaviorName()
    {
        return 'dashboard-query-panel-select';
    }

    /**
     * @var array
     */
    public $js = [
        'js/behavior-dashboard-query-panel-select.js',
    ];

    /**
     * @var array
     */
    public $depends = [
        'orangins\modules\widgets\javelin\JavelinAsset',
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