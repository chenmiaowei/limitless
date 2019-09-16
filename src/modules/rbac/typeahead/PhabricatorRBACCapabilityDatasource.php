<?php

namespace orangins\modules\rbac\typeahead;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\PhabricatorApplication;
use orangins\lib\view\phui\PHUIIconView;
use orangins\modules\dashboard\application\PhabricatorDashboardApplication;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;
use orangins\modules\typeahead\model\PhabricatorTypeaheadResult;

/**
 * Class PhabricatorDashboardDatasource
 * @package orangins\modules\dashboard\typeahead
 * @author 陈妙威
 */
final class PhabricatorRBACCapabilityDatasource extends PhabricatorTypeaheadDatasource
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return \Yii::t("app", '浏览节点列表');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return \Yii::t("app", '请输入节点的描述');
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
     * @throws \ReflectionException
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
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function renderSpecialTokens(array $values)
    {
        return $this->renderTokensFromResults($this->buildResults(), $values);
    }

    /**
     * @return array
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    private function buildResults()
    {
        $capabilityMap = PhabricatorPolicyCapability::getCapabilityMap();
        $results = array();
        foreach ($capabilityMap as $policyCapability) {
            $application = PhabricatorApplication::getByClass($policyCapability->getApplicationClassName());
            $result = (new PhabricatorTypeaheadResult())
                ->setName($policyCapability->getCapabilityName())
                ->setPHID($policyCapability->getCapabilityKey());

            $result->addAttribute(
                JavelinHtml::phutil_tag("div", [
                    "class" => "badge bg-" . PhabricatorEnv::getEnvConfig("ui.widget-color")
                ], array(
                    (new PHUIIconView())->setIcon($application->getIcon()),
                    ' ',
                    \Yii::t("app", $application->getName()),
                )));
            $results[] = $result;
        }
        return $results;
    }
}
