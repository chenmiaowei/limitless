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
abstract class PhabricatorDashboardPanelChartCountDataSourceEngine
{
    /**
     * @param string $startDate Y-m-d
     * @param string $endDate Y-m-d
     * @return array
     * @author 陈妙威
     */
    abstract public function getData($startDate = null, $endDate = null);

    /**
     * @return string
     * @author 陈妙威
     */
    abstract public function getDescription();

    /**
     * @return string
     * @author 陈妙威
     */
    abstract public function getKey();

    /**
     * @return PhabricatorDashboardPanelChartLineDataSourceEngine[]
     * @author 陈妙威
     */
    public static function getAllEngines()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getKey')
            ->execute();
    }
}