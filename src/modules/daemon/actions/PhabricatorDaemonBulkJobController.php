<?php

namespace orangins\modules\daemon\actions;

use orangins\lib\infrastructure\daemon\workers\query\PhabricatorWorkerBulkJobSearchEngine;
use orangins\lib\view\phui\PHUIListView;

/**
 * Class PhabricatorDaemonBulkJobController
 * @package orangins\modules\daemon\actions
 * @author 陈妙威
 */
abstract class PhabricatorDaemonBulkJobController
    extends PhabricatorDaemonController
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireAdmin()
    {
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPublic()
    {
        return true;
    }

    /**
     * @return null|\orangins\lib\view\layout\PHUIApplicationMenuView|PHUIListView
     * @author 陈妙威
     */
    public function buildApplicationMenu()
    {
        return $this->controller->newApplicationMenu()
            ->setSearchEngine(new PhabricatorWorkerBulkJobSearchEngine());
    }

    /**
     * @return \orangins\lib\view\phui\PHUICrumbsView
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function buildApplicationCrumbs()
    {
        $crumbs = parent::buildApplicationCrumbs();
        $crumbs->addTextCrumb(\Yii::t("app", 'Bulk Jobs'), '/daemon/bulk/');
        return $crumbs;
    }

}
