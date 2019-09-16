<?php

namespace orangins\modules\dashboard\models;

use orangins\modules\search\ngrams\PhabricatorSearchNgrams;

/**
 * Class PhabricatorDashboardNgrams
 * @package orangins\modules\dashboard\models
 * @author 陈妙威
 */
final class PhabricatorDashboardNgrams extends PhabricatorSearchNgrams
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getNgramKey()
    {
        return 'dashboard';
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
