<?php

namespace orangins\modules\dashboard\editors;

use orangins\modules\dashboard\application\PhabricatorDashboardApplication;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;

/**
 * Class PhabricatorDashboardPanelTransactionEditor
 * @package orangins\modules\dashboard\editors
 * @author 陈妙威
 */
final class PhabricatorDashboardPanelTransactionEditor
    extends PhabricatorApplicationTransactionEditor
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditorApplicationClass()
    {
        return PhabricatorDashboardApplication::className();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditorObjectsDescription()
    {
        return pht('Dashboard Panels');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getTransactionTypes()
    {
        $types = parent::getTransactionTypes();

        $types[] = PhabricatorTransactions::TYPE_VIEW_POLICY;
        $types[] = PhabricatorTransactions::TYPE_EDIT_POLICY;
        $types[] = PhabricatorTransactions::TYPE_EDGE;

        return $types;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function supportsSearch()
    {
        return true;
    }
}
