<?php

namespace orangins\modules\dashboard\xaction\panel;

/**
 * Class PhabricatorDashboardChartLinePanelDataTransaction
 * @package orangins\modules\dashboard\xaction\panel
 * @author 陈妙威
 */
final class PhabricatorDashboardChartPanelTimeTransaction
    extends PhabricatorDashboardPanelPropertyTransaction
{
    /**
     *
     */
    const TRANSACTIONTYPE = 'chart.time';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getPropertyKey()
    {
        return 'chart_time';
    }
}