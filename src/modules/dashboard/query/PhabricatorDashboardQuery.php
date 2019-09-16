<?php

namespace orangins\modules\dashboard\query;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\dashboard\application\PhabricatorDashboardApplication;
use orangins\modules\dashboard\models\PhabricatorDashboard;
use orangins\modules\dashboard\models\PhabricatorDashboardNgrams;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;

/**
 * Class PhabricatorDashboardQuery
 * @package orangins\modules\dashboard\query
 * @author 陈妙威
 */
final class PhabricatorDashboardQuery
    extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     * @var
     */
    private $ids;
    /**
     * @var
     */
    private $phids;
    /**
     * @var
     */
    private $statuses;
    /**
     * @var
     */
    private $authorPHIDs;
    /**
     * @var
     */
    private $canEdit;

    /**
     * @var
     */
    private $needPanels;
    /**
     * @var
     */
    private $needProjects;

    /**
     * @param array $ids
     * @return $this
     * @author 陈妙威
     */
    public function withIDs(array $ids)
    {
        $this->ids = $ids;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withPHIDs(array $phids)
    {
        $this->phids = $phids;
        return $this;
    }

    /**
     * @param array $statuses
     * @return $this
     * @author 陈妙威
     */
    public function withStatuses(array $statuses)
    {
        $this->statuses = $statuses;
        return $this;
    }

    /**
     * @param array $authors
     * @return $this
     * @author 陈妙威
     */
    public function withAuthorPHIDs(array $authors)
    {
        $this->authorPHIDs = $authors;
        return $this;
    }

    /**
     * @param $need_panels
     * @return $this
     * @author 陈妙威
     */
    public function needPanels($need_panels)
    {
        $this->needPanels = $need_panels;
        return $this;
    }

    /**
     * @param $need_projects
     * @return $this
     * @author 陈妙威
     */
    public function needProjects($need_projects)
    {
        $this->needProjects = $need_projects;
        return $this;
    }

    /**
     * @param $can_edit
     * @return $this
     * @author 陈妙威
     */
    public function withCanEdit($can_edit)
    {
        $this->canEdit = $can_edit;
        return $this;
    }

    /**
     * @param $ngrams
     * @return PhabricatorDashboardQuery
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function withNameNgrams($ngrams)
    {
        return $this->withNgramsConstraint((new PhabricatorDashboardNgrams()), $ngrams);
    }

    /**
     * @return array|null|\yii\db\ActiveRecord[]
     * @author 陈妙威
     * @throws \Exception
     */
    protected function loadPage()
    {
        return $this->loadStandardPage();
    }


    /**
     * @return null|PhabricatorDashboard
     * @author 陈妙威
     */
    public function newResultObject() {
        return new PhabricatorDashboard();
    }

    /**
     * @param array $dashboards
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function didFilterPage(array $dashboards) {

        $phids = mpull($dashboards, 'getPHID');

        if ($this->canEdit) {
            $dashboards = (new  PhabricatorPolicyFilter())
                ->setViewer($this->getViewer())
                ->requireCapabilities(array(
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
                ->apply($dashboards);
        }

        return $dashboards;
    }

    /**
     * @throws \PhutilInvalidStateException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException
     * @throws \orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function buildWhereClauseParts()
    {
        parent::buildWhereClauseParts();

        if ($this->ids !== null) {
            $this->andWhere(['IN', 'id', $this->ids]);
        }

        if ($this->phids !== null) {
            $this->andWhere(['IN', 'phid', $this->phids]);
        }

        if ($this->statuses !== null) {
            $this->andWhere(['IN', 'status', $this->statuses]);
        }

        if ($this->authorPHIDs !== null) {
            $this->andWhere(['IN', 'author_phid', $this->authorPHIDs]);
        }
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorDashboardApplication::class;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getPrimaryTableAlias()
    {
        return 'dashboard';
    }

}
