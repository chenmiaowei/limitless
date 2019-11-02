<?php

namespace orangins\lib\infrastructure\customfield\datasource;

use orangins\lib\infrastructure\query\constraint\PhabricatorQueryConstraint;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;
use orangins\modules\typeahead\model\PhabricatorTypeaheadResult;
use orangins\modules\typeahead\view\PhabricatorTypeaheadTokenView;
use ReflectionException;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Class PhabricatorCustomFieldApplicationSearchNoneFunctionDatasource
 * @package orangins\lib\infrastructure\customfield\datasource
 * @author 陈妙威
 */
final class PhabricatorCustomFieldApplicationSearchNoneFunctionDatasource
    extends PhabricatorTypeaheadDatasource
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return pht('Browse No Value');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return pht('Type "none()"...');
    }

    /**
     * @return mixed|null
     * @author 陈妙威
     */
    public function getDatasourceApplicationClass()
    {
        return null;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getDatasourceFunctions()
    {
        return array(
            'none' => array(
                'name' => pht('No Value'),
                'summary' => pht('Find results with no value.'),
                'description' => pht(
                    "This function includes results which have no value. Use a query " .
                    "like this to find results with no value:\n\n%s\n\n",
                    'If you combine this function with other constraints, results ' .
                    'which have no value or the specified values will be returned.',
                    '> any()'),
            ),
        );
    }

    /**
     * @return mixed|PhabricatorTypeaheadResult[]
     * @throws Exception
     * @author 陈妙威
     */
    public function loadResults()
    {
        $results = array(
            $this->newNoneFunction(),
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
            $results[] = new PhabricatorQueryConstraint(
                PhabricatorQueryConstraint::OPERATOR_NULL,
                null);
        }

        return $results;
    }

    /**
     * @param $function
     * @param array $argv_list
     * @return array|PhabricatorTypeaheadTokenView[]
     * @throws ReflectionException
     * @throws Exception
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    public function renderFunctionTokens($function, array $argv_list)
    {
        $results = array();
        foreach ($argv_list as $argv) {
            $results[] = PhabricatorTypeaheadTokenView::newFromTypeaheadResult(
                $this->newNoneFunction());
        }
        return $results;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function newNoneFunction()
    {
        $name = pht('No Value');
        return $this->newFunctionResult()
            ->setName($name . ' none')
            ->setDisplayName($name)
            ->setIcon('fa-ban')
            ->setPHID('none()')
            ->setUnique(true)
            ->addAttribute(pht('Select results with no value.'));
    }

}
