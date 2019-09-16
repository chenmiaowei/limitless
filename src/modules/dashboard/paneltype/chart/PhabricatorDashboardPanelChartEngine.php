<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/5/8
 * Time: 2:40 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\dashboard\paneltype\chart;


use PhutilClassMapQuery;

/**
 * Class PhabricatorDashboardPanelChartLineEngine
 * @package orangins\modules\dashboard\paneltype\chart
 * @author 陈妙威
 */
abstract class PhabricatorDashboardPanelChartEngine
{
    /**
     * 图表仪表盘类型主键
     * @return string
     * @author 陈妙威
     */
    abstract public function getChartTypeKey();

    /**
     * 图表仪表盘类型解释
     * @return string
     * @author 陈妙威
     */
    abstract public function getChartTypeDesc();

    /**
     * @return PhabricatorDashboardPanelChartEngine[]
     * @author 陈妙威
     */
    public static function getAllEngines()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getChartTypeKey')
            ->execute();
    }
}