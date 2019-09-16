<?php

namespace orangins\modules\dashboard\actions;

use orangins\lib\actions\PhabricatorAction;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\modules\dashboard\models\PhabricatorDashboard;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorDashboardProfileController
 * @package orangins\modules\dashboard\actions
 * @author 陈妙威
 */
abstract class PhabricatorDashboardProfileController
    extends PhabricatorAction
{

    /**
     * @var
     */
    private $dashboard;

    /**
     * @param PhabricatorDashboard $dashboard
     * @return $this
     * @author 陈妙威
     */
    public function setDashboard(PhabricatorDashboard $dashboard)
    {
        $this->dashboard = $dashboard;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDashboard()
    {
        return $this->dashboard;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function buildHeaderView()
    {
        $viewer = $this->getViewer();
        $dashboard = $this->getDashboard();
        $id = $dashboard->getID();

        if ($dashboard->isArchived()) {
            $status_icon = 'fa-ban';
            $status_color = 'grey';
        } else {
            $status_icon = 'fa-check';
            $status_color = 'success';
        }

        $status_name = ArrayHelper::getValue(
            PhabricatorDashboard::getStatusNameMap(),
            $dashboard->getStatus());

        return (new PHUIPageHeaderView())
            ->setUser($viewer)
            ->setHeader($dashboard->getName())
            ->setPolicyObject($dashboard)
            ->setStatus($status_icon, $status_color, $status_name)
            ->setHeaderIcon($dashboard->getIcon());
    }

    /**
     * @return \orangins\lib\view\phui\PHUICrumbsView
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function buildApplicationCrumbs()
    {
        $crumbs = parent::buildApplicationCrumbs();
        $crumbs->setBorder(true);

        $dashboard = $this->getDashboard();
        if ($dashboard) {
            $crumbs->addTextCrumb($dashboard->getName(), $dashboard->getURI());
        }

        return $crumbs;
    }

}
