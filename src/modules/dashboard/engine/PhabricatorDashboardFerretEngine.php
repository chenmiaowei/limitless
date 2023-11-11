<?php

namespace orangins\modules\dashboard\engine;

use orangins\modules\dashboard\query\PhabricatorDashboardSearchEngine;
use orangins\modules\search\ferret\PhabricatorFerretEngine;

/**
 * Class PhabricatorDashboardFerretEngine
 * @package orangins\modules\dashboard\engine
 * @author 陈妙威
 */
final class PhabricatorDashboardFerretEngine
    extends PhabricatorFerretEngine
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationName()
    {
        return 'dashboard';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getScopeName()
    {
        return 'dashboard';
    }

    /**
     * @return mixed|PhabricatorDashboardSearchEngine
     * @author 陈妙威
     */
    public function newSearchEngine()
    {
        return new PhabricatorDashboardSearchEngine();
    }

}
