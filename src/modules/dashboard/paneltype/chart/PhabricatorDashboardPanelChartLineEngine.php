<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/5/9
 * Time: 4:54 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\dashboard\paneltype\chart;


class PhabricatorDashboardPanelChartLineEngine extends PhabricatorDashboardPanelChartEngine
{

    /**
     * 图表仪表盘类型主键
     * @return string
     * @author 陈妙威
     */
    public function getChartTypeKey()
    {
        return 'line';
    }

    /**
     * 图表仪表盘类型解释
     * @return string
     * @author 陈妙威
     */
    public function getChartTypeDesc()
    {
        return '折线图';
    }
}