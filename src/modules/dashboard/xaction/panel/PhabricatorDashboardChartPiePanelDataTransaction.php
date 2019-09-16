<?php

namespace orangins\modules\dashboard\xaction\panel;

/**
 * Class PhabricatorDashboardChartLinePanelDataTransaction
 * @package orangins\modules\dashboard\xaction\panel
 * @author 陈妙威
 */
final class PhabricatorDashboardChartPiePanelDataTransaction
    extends PhabricatorDashboardPanelPropertyTransaction
{
    /**
     *
     */
    const TRANSACTIONTYPE = 'pie.datasource';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getPropertyKey()
    {
        return 'pie_datasource';
    }
}
