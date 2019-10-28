<?php

namespace orangins\modules\herald\query;

use orangins\modules\herald\view\HeraldRuleappliedTableView;
use orangins\modules\herald\models\HeraldRuleapplied;
use orangins\lib\helpers\OranginsUtil;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\field\PhabricatorSearchTextField;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorXgbzxrSearchEngine
 * @package orangins\modules\file\query
 * @author 陈妙威
 */
final class HeraldRuleappliedSearchEngine extends PhabricatorApplicationSearchEngine
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return \Yii::t("app", 'Herald Ruleapplied');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return \orangins\modules\herald\application\PhabricatorHeraldApplication::className();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function canUseInPanelContext()
    {
        return false;
    }

    /**
     * @throws \yii\base\InvalidConfigException
     * @return HeraldRuleappliedQuery     */
    public function newQuery()
    {
        $query = HeraldRuleapplied::find();
        return $query;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function buildCustomSearchFields()
    {
        return array(

            (new PhabricatorSearchTextField())
                ->setKey('rule_id')
                ->setLabel(\Yii::t("app", 'Rule Id')),
            (new PhabricatorSearchTextField())
                ->setKey('phid')
                ->setLabel(\Yii::t("app", 'Phid')),
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getDefaultFieldOrder()
    {
        return array(
            '...',
            'createdStart',
            'createdEnd',
        );
    }

    /**
     * @param array $map
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\Exception
     * @return HeraldRuleappliedQuery     */
    protected function buildQueryFromParameters(array $map)
    {
        $query = $this->newQuery();

        if ($map['rule_id']) {
            $query->withRule_id($map['rule_id']);
        }
        if ($map['phid']) {
            $query->withPHID($map['phid']);
        }
        return $query;
    }

    /**
     * @param $path
     * @param array $params
     * @return string
     * @author 陈妙威
     */
    protected function getURI($path = null, $params = [])
    {
        return Url::to(ArrayHelper::merge([
            '/herald/herald-ruleapplied/' . $path
        ], $params));
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getBuiltinQueryNames()
    {
        $names = array();
        $names += array(
            'all' => \Yii::t("app", 'All'),
        );
        return $names;
    }

    /**
    * @param $query_key
    * @return mixed
    * @throws \ReflectionException
    * @throws \Exception
    * @author 陈妙威
    */
    public function buildSavedQueryFromBuiltin($query_key)
    {
        $query = $this->newSavedQuery();
        $query->setQueryKey($query_key);

        switch ($query_key) {
            case 'all':
                return $query;
        }

        return parent::buildSavedQueryFromBuiltin($query_key);
    }

    /**
     * @param HeraldRuleapplied[] $files
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return PhabricatorApplicationSearchResultView
     */
    protected function renderResultList(
        array $files,
        PhabricatorSavedQuery $query,
        array $handles)
    {

        OranginsUtil::assert_instances_of($files, HeraldRuleapplied::className());

        $tableView = new HeraldRuleappliedTableView();
        $tableView->setItems($files);
        $tableView->setNoDataString(\Yii::t("app", 'No Data'));

        $result = new PhabricatorApplicationSearchResultView();
        $result->setTable($tableView);

        return $result;
    }
}

