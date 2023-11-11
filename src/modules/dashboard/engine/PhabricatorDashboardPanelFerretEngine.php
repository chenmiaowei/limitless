<?php

namespace orangins\modules\dashboard\engine;

use orangins\modules\dashboard\query\PhabricatorDashboardPanelSearchEngine;
use orangins\modules\search\ferret\PhabricatorFerretEngine;

/**
 * Class PhabricatorDashboardPanelFerretEngine
 * @package orangins\modules\dashboard\engine
 * @author 陈妙威
 */
final class PhabricatorDashboardPanelFerretEngine
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
        return 'panel';
    }

    /**
     * @return mixed|PhabricatorDashboardPanelSearchEngine
     * @author 陈妙威
     */
    public function newSearchEngine()
    {
        return new PhabricatorDashboardPanelSearchEngine();
    }

}
