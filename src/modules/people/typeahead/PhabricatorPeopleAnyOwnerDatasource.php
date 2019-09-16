<?php

namespace orangins\modules\people\typeahead;

use orangins\modules\people\application\PhabricatorPeopleApplication;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;

/**
 * Class PhabricatorPeopleAnyOwnerDatasource
 * @package orangins\modules\people\typeahead
 * @author 陈妙威
 */
final class PhabricatorPeopleAnyOwnerDatasource
    extends PhabricatorTypeaheadDatasource
{

    /**
     *
     */
    const FUNCTION_TOKEN = 'anyone()';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return \Yii::t("app",'Browse Any Owner');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return \Yii::t("app",'Type "anyone()"...');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getDatasourceApplicationClass()
    {
        return PhabricatorPeopleApplication::class;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getDatasourceFunctions()
    {
        return array(
            'anyone' => array(
                'name' => \Yii::t("app",'Anyone'),
                'summary' => \Yii::t("app",'Find results which are assigned to anyone.'),
                'description' => \Yii::t("app",
                    'This function includes results which have any owner. It excludes ' .
                    'unassigned or unowned results.'),
            ),
        );
    }

    /**
     * @return \orangins\modules\typeahead\datasource\list|mixed
     * @author 陈妙威
     */
    public function loadResults()
    {
        $results = array(
            $this->buildAnyoneResult(),
        );
        return $this->filterResultsAgainstTokens($results);
    }

    /**
     * @param $function
     * @param array $argv_list
     * @return array|void
     * @author 陈妙威
     */
    protected function evaluateFunction($function, array $argv_list)
    {
        $results = array();

        foreach ($argv_list as $argv) {
            $results[] = self::FUNCTION_TOKEN;
        }

        return $results;
    }

    /**
     * @param $function
     * @param array $argv_list
     * @return array|void
     * @author 陈妙威
     */
    public function renderFunctionTokens($function, array $argv_list)
    {
        $results = array();
        foreach ($argv_list as $argv) {
            $results[] = PhabricatorTypeaheadTokenView::newFromTypeaheadResult(
                $this->buildAnyoneResult());
        }
        return $results;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function buildAnyoneResult()
    {
        $name = \Yii::t("app",'Any Owner');
        return $this->newFunctionResult()
            ->setName($name . ' anyone')
            ->setDisplayName($name)
            ->setIcon('fa-certificate')
            ->setPHID(self::FUNCTION_TOKEN)
            ->setUnique(true)
            ->addAttribute(\Yii::t("app",'Select results with any owner.'));
    }

}
