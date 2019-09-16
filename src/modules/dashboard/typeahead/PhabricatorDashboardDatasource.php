<?php

namespace orangins\modules\dashboard\typeahead;

use orangins\modules\dashboard\application\PhabricatorDashboardApplication;
use orangins\modules\dashboard\models\PhabricatorDashboard;
use orangins\modules\dashboard\paneltype\chart\PhabricatorDashboardPanelChartLineDataSourceEngine;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;
use orangins\modules\typeahead\model\PhabricatorTypeaheadResult;

/**
 * Class PhabricatorDashboardDatasource
 * @package orangins\modules\dashboard\typeahead
 * @author 陈妙威
 */
final class PhabricatorDashboardDatasource extends PhabricatorTypeaheadDatasource
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return \Yii::t("app",'Browse Dashboards');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return \Yii::t("app",'Type a dashboard name...');
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
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function loadResults()
    {
        $query = PhabricatorDashboard::find();

        /** @var PhabricatorDashboard[] $dashboards */
        $dashboards = $this->executeQuery($query);
        $results = array();
        foreach ($dashboards as $dashboard) {
            $result = (new PhabricatorTypeaheadResult())
                ->setName($dashboard->getName())
                ->setPHID($dashboard->getPHID())
                ->addAttribute(\Yii::t("app",'Dashboard'));

            if ($dashboard->isArchived()) {
                $result->setClosed(\Yii::t("app",'Archived'));
            }

            $results[] = $result;
        }

        return $this->filterResultsAgainstTokens($results);
    }


}
