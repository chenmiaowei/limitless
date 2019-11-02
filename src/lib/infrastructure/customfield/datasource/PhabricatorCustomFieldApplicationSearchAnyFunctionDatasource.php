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
 * Class PhabricatorCustomFieldApplicationSearchAnyFunctionDatasource
 * @package orangins\lib\infrastructure\customfield\datasource
 * @author 陈妙威
 */
final class PhabricatorCustomFieldApplicationSearchAnyFunctionDatasource
    extends PhabricatorTypeaheadDatasource
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return pht('Browse Any');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return pht('Type "any()"...');
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
            'any' => array(
                'name' => pht('Any Value'),
                'summary' => pht('Find results with any value.'),
                'description' => pht(
                    "This function includes results which have any value. Use a query " .
                    "like this to find results with any value:\n\n%s",
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
            $this->newAnyFunction(),
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
                PhabricatorQueryConstraint::OPERATOR_ANY,
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
                $this->newAnyFunction());
        }
        return $results;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function newAnyFunction()
    {
        $name = pht('Any Value');
        return $this->newFunctionResult()
            ->setName($name . ' any')
            ->setDisplayName($name)
            ->setIcon('fa-circle-o')
            ->setPHID('any()')
            ->setUnique(true)
            ->addAttribute(pht('Select results with any value.'));
    }

}
