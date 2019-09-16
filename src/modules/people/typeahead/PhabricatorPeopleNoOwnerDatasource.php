<?php

namespace orangins\modules\people\typeahead;

use orangins\modules\people\application\PhabricatorPeopleApplication;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;

/**
 * Class PhabricatorPeopleNoOwnerDatasource
 * @package orangins\modules\people\typeahead
 * @author 陈妙威
 */
final class PhabricatorPeopleNoOwnerDatasource
    extends PhabricatorTypeaheadDatasource
{

    /**
     *
     */
    const FUNCTION_TOKEN = 'none()';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return \Yii::t("app",'Browse No Owner');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return \Yii::t("app",'Type "none"...');
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
            'none' => array(
                'name' => \Yii::t("app",'No Owner'),
                'summary' => \Yii::t("app",'Find results which are not assigned.'),
                'description' => \Yii::t("app",
                    "This function includes results which have no owner. Use a query " .
                    "like this to find unassigned results:\n\n%s\n\n" .
                    "If you combine this function with other functions, the query will " .
                    "return results which match the other selectors //or// have no " .
                    "owner. For example, this query will find results which are owned " .
                    "by `alincoln`, and will also find results which have no owner:" .
                    "\n\n%s",
                    '> none()',
                    '> alincoln, none()'),
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
            $this->buildNoOwnerResult(),
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
                $this->buildNoOwnerResult());
        }
        return $results;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function buildNoOwnerResult()
    {
        $name = \Yii::t("app",'No Owner');
        return $this->newFunctionResult()
            ->setName($name . ' none')
            ->setDisplayName($name)
            ->setIcon('fa-ban')
            ->setPHID('none()')
            ->setUnique(true)
            ->addAttribute(\Yii::t("app",'Select results with no owner.'));
    }

}
