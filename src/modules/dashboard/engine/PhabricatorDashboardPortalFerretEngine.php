<?php

namespace orangins\modules\dashboard\engine;

use orangins\modules\search\ferret\PhabricatorFerretEngine;

/**
 * Class PhabricatorDashboardPortalFerretEngine
 * @package orangins\modules\dashboard\engine
 * @author 陈妙威
 */
final class PhabricatorDashboardPortalFerretEngine
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
        return 'portal';
    }

    /**
     * @return mixed|PhabricatorDashboardPortalSearchEngine
     * @author 陈妙威
     */
    public function newSearchEngine()
    {
        return new PhabricatorDashboardPortalSearchEngine();
    }

}
