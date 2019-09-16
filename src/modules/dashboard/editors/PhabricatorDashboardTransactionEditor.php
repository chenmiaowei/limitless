<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/9/5
 * Time: 10:39 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\dashboard\editors;

use orangins\modules\dashboard\application\PhabricatorDashboardApplication;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;

/**
 * Class DashboardEntityEditor
 * @package orangins\modules\dashboard\editors
 * @author 陈妙威
 */
class PhabricatorDashboardTransactionEditor extends PhabricatorApplicationTransactionEditor
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
        return pht('Dashboards');
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
