<?php

namespace orangins\modules\dashboard\typeahead;

use orangins\modules\dashboard\application\PhabricatorDashboardApplication;
use orangins\modules\dashboard\models\PhabricatorDashboardPanel;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;
use orangins\modules\typeahead\model\PhabricatorTypeaheadResult;

/**
 * Class PhabricatorDashboardPanelDatasource
 * @package orangins\modules\dashboard\typeahead
 * @author 陈妙威
 */
final class PhabricatorDashboardPanelDatasource
    extends PhabricatorTypeaheadDatasource
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return \Yii::t("app",'Browse Dashboard Panels');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return \Yii::t("app",'Type a panel name...');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getDatasourceApplicationClass()
    {
        return PhabricatorDashboardApplication::class;
    }

    /**
     * @return mixed|\orangins\modules\typeahead\model\PhabricatorTypeaheadResult[]
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function loadResults()
    {
        $results = $this->buildResults();
        return $this->filterResultsAgainstTokens($results);
    }

    /**
     * @param array $values
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function renderSpecialTokens(array $values)
    {
        return $this->renderTokensFromResults($this->buildResults(), $values);
    }

    /**
     * @return array
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    public function buildResults()
    {
        $query = PhabricatorDashboardPanel::find();
        $raw_query = $this->getRawQuery();
        if (preg_match('/^[wW]\d+\z/', $raw_query)) {
            $id = trim($raw_query, 'wW');
            $id = (int)$id;
            $query->withIDs(array($id));
        } else {
            $query->withNameNgrams($raw_query);
        }

        /** @var PhabricatorDashboardPanel[] $panels */
        $panels = $this->executeQuery($query);

        $results = array();
        foreach ($panels as $panel) {
            $impl = $panel->getImplementation();
            if ($impl) {
                $type_text = $impl->getPanelTypeName();
                $icon = $impl->getIcon();
            } else {
                $type_text = nonempty($panel->getPanelType(), \Yii::t("app",'Unknown Type'));
                $icon = 'fa-question';
            }
            $id = $panel->getPHID();
            $monogram = $panel->getMonogram();
            $properties = $panel->getProperties();

            $result = (new PhabricatorTypeaheadResult())
                ->setName($monogram . ' ' . $panel->getName())
                ->setPHID($id)
                ->setIcon($icon)
                ->addAttribute($type_text);

            if (!empty($properties['class'])) {
                $result->addAttribute($properties['class']);
            }

            if ($panel->getIsArchived()) {
                $result->setClosed(\Yii::t("app",'Archived'));
            }

            $results[$id] = $result;
        }

        return $results;
    }

}
