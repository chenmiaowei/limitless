<?php

namespace orangins\modules\dashboard\models;

use orangins\modules\search\ngrams\PhabricatorSearchNgrams;

/**
 * Class PhabricatorDashboardPanelNgrams
 * @package orangins\modules\dashboard\models
 * @author 陈妙威
 */
final class PhabricatorDashboardPanelNgrams extends PhabricatorSearchNgrams
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getNgramKey()
    {
        return 'dashboardpanel';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getColumnName()
    {
        return 'name';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationName()
    {
        return 'dashboard';
    }

}
