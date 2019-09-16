<?php

namespace orangins\modules\dashboard\typeahead;

use orangins\modules\dashboard\application\PhabricatorDashboardApplication;
use orangins\modules\dashboard\paneltype\chart\PhabricatorDashboardPanelChartCountDataSourceEngine;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;
use orangins\modules\typeahead\model\PhabricatorTypeaheadResult;

/**
 * Class PhabricatorDashboardDatasource
 * @package orangins\modules\dashboard\typeahead
 * @author 陈妙威
 */
final class PhabricatorDashboardPanelChartCountDatasource extends PhabricatorTypeaheadDatasource
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return \Yii::t("app",'浏览仪表板图标数据源');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return \Yii::t("app",'请输入数据源名称');
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
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function loadResults()
    {
        $allEngines = PhabricatorDashboardPanelChartCountDataSourceEngine::getAllEngines();

        $results = array();
        foreach ($allEngines as $dashboard) {
            $result = (new PhabricatorTypeaheadResult())
                ->setName($dashboard->getDescription())
                ->setPHID($dashboard->getKey());
            $results[] = $result;
        }
        return $this->filterResultsAgainstTokens($results);
    }


    /**
     * @param array $values
     * @return array
     * @author 陈妙威
     */
    protected function renderSpecialTokens(array $values) {
        return $this->renderTokensFromResults($this->buildResults(), $values);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    private function buildResults() {
        $types = PhabricatorDashboardPanelChartCountDataSourceEngine::getAllEngines();
        $results = array();
        foreach ($types as$type) {
            $results[$type->getKey()] = (new PhabricatorTypeaheadResult())
                ->setName($type->getDescription())
                ->setPHID($type->getKey());
        }
        return $results;
    }
}
