<?php

namespace orangins\modules\dashboard\query;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\dashboard\application\PhabricatorDashboardApplication;
use orangins\modules\dashboard\models\PhabricatorDashboardPanel;
use orangins\modules\dashboard\models\PhabricatorDashboardPanelNgrams;
use orangins\modules\dashboard\paneltype\PhabricatorDashboardTextPanelType;

/**
 * Class PhabricatorDashboardPanelQuery
 * @package orangins\modules\dashboard\query
 * @author 陈妙威
 */
final class PhabricatorDashboardPanelQuery
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
    private $archived;
    /**
     * @var
     */
    private $panelTypes;
    /**
     * @var
     */
    private $authorPHIDs;

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
     * @param $archived
     * @return $this
     * @author 陈妙威
     */
    public function withArchived($archived)
    {
        $this->archived = $archived;
        return $this;
    }

    /**
     * @param array $types
     * @return $this
     * @author 陈妙威
     */
    public function withPanelTypes(array $types)
    {
        $this->panelTypes = $types;
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
     * @param $ngrams
     * @return PhabricatorDashboardPanelQuery
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function withNameNgrams($ngrams)
    {
        return $this->withNgramsConstraint((new PhabricatorDashboardPanelNgrams()), $ngrams);
    }

    /**
     * @return array|null|\yii\db\ActiveRecord[]
     * @author 陈妙威
     */
    protected function loadPage()
    {
        return $this->loadStandardPage();
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function newResultObject()
    {
        // TODO: If we don't do this, SearchEngine explodes when trying to
        // enumerate custom fields. For now, just give the panel a default panel
        // type so custom fields work. In the long run, we may want to find a
        // cleaner or more general approach for this.
        $text_type = (new PhabricatorDashboardTextPanelType())
            ->getPanelTypeKey();

        return (new PhabricatorDashboardPanel())
            ->setPanelType($text_type);
    }

    /**
     * @return array|void
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

        if ($this->archived !== null) {
            $this->andWhere(['is_archived' => $this->archived]);
        }

        if ($this->panelTypes !== null) {
            $this->andWhere(['IN', 'panel_type', $this->panelTypes]);
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
        return 'dashboard_panel';
    }

}
