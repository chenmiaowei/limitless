<?php

namespace orangins\modules\dashboard\xaction\panel;

/**
 * Class PhabricatorDashboardChartLinePanelDataTransaction
 * @package orangins\modules\dashboard\xaction\panel
 * @author 陈妙威
 */
final class PhabricatorDashboardChartCountPanelDataTransaction
    extends PhabricatorDashboardPanelPropertyTransaction
{
    /**
     *
     */
    const TRANSACTIONTYPE = 'count.datasource';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getPropertyKey()
    {
        return 'count_datasource';
    }
}
