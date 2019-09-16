<?php

namespace orangins\modules\dashboard\xaction\panel;

/**
 * Class PhabricatorDashboardChartLinePanelDataTransaction
 * @package orangins\modules\dashboard\xaction\panel
 * @author 陈妙威
 */
final class PhabricatorDashboardChartLinePanelDataTransaction
    extends PhabricatorDashboardPanelPropertyTransaction
{
    /**
     *
     */
    const TRANSACTIONTYPE = 'line.datasource';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getPropertyKey()
    {
        return 'line_datasource';
    }
}
